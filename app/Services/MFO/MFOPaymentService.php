<?php

namespace App\Services\MFO;

use App\Helpers\SellerBonusesHelper;
use App\Models\AvailablePeriod;
use App\Models\CancelContract;
use App\Models\Contract;
use App\Models\ContractPaymentsSchedule;
use App\Models\MFOPayment;
use App\Traits\UzTaxTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class MFOPaymentService
{
    private WalletService $wallet_service;

    public function __construct()
    {
        $this->wallet_service = new WalletService();
    }

    public function init(Contract $contract) : bool
    {
        $this->createTransaction($contract);

        $account_service = new AccountService();
        $account_entry_service = new AccountingEntryService();

        $account_service->init($contract->id);
        $account_entry_service->init($contract->id);

        return true;
    }

    private function createTransaction(Contract $contract) : bool
    {
        try {
            $buyer_wallet = $this->wallet_service->getWallet($contract->user_id,WalletService::TYPE_BUYER);
            $mfo_wallet = $this->wallet_service->getWallet($contract->general_company_id,WalletService::TYPE_MFO);
            $solution_wallet = $this->wallet_service->getWallet(1,WalletService::TYPE_SOLUTION);
            $partner_wallet = $this->wallet_service->getWallet($contract->partner_id,WalletService::TYPE_PARTNER);

            //Make transaction to buyer wallet
            $this->makeTransaction($contract,$contract->total);
            $buyer_wallet->setBalance($contract->total + $buyer_wallet->getBalance());

            //Update MFO wallet
            $mfo_amount = $contract->order->partner_total;
            $period = AvailablePeriod::query()->find($contract->price_plan_id);
            if($period && $period->reverse_calc){
                $reverse_calc_percent = Config::get('test.mfo_reverse_calc_amount');
                $mfo_amount = (1 - $reverse_calc_percent) * (float)$contract->total;
            }
            $mfo_wallet->setBalance($mfo_wallet->getBalance() - $mfo_amount);

            //Make transaction to partner wallet
            $payment_transaction = $this->makeTransaction($contract,$mfo_amount,MFOPayment::TYPE_PAYMENT);

            //Update partner wallet
            $partner_wallet->setBalance($partner_wallet->getBalance() + $payment_transaction->amount);

            //Make transaction to Solution Lab
            $service_transaction = $this->makeTransaction($contract,$contract->total - $mfo_amount,MFOPayment::TYPE_SERVICE);
            $solution_wallet->setBalance($service_transaction->amount + $solution_wallet->getBalance());

            //Update buyer wallet
            $buyer_wallet->setBalance($buyer_wallet->getBalance() - ($payment_transaction->amount + $service_transaction->amount));
            return true;
        }
        catch (\Exception $exception){
            Log::channel('mfo_account_errors')->info('MFOPayment:createTransaction->error: '.$exception->getMessage());
            return false;
        }
    }

    public function cancelTransactionSendSms(Contract $contract) : array
    {
        if($contract->status !== Contract::STATUS_ACTIVE){
            return ['status' => 'error','message' => __('app.err_not_found')];
        }
        if(Carbon::now()->diffInMonths($contract->confirmed_at) > 1){
            return ['status' => 'error','message' => __('billing/order.text_expired')];
        }
        $buyer = $contract->buyer;
        $message = 'Kod :code .'. date('Y.m.d',strtotime($contract->created_at)) . ' da rasmiylashtirilgan ' . $contract->id . ' shartnomani bekor qilish kodi. Tel: ' . callCenterNumber(2);
        $send_sms_service = new MFOOrderService();
        $result = $send_sms_service->sendSmsCode(correct_phone($buyer->phone),$message);
        if($result['code'] === 1){
            return ['status' => 'success','data' => ['hashedCode' => $result['data']]];
        }
        return ['status' => 'error','message' => __('api.internal_server_error')];
    }

    public function cancelTransactionCheckSms(Contract $contract) : array
    {
        if(date('Y-m-d',strtotime($contract->confirmed_at)) == date('Y-m-d')){
            $result = $this->closeContractOnThisDay($contract);
        }else{
            $result = $this->repayment($contract);
        }
        $entry_service = new AccountingEntryService();
        $entry_service->createEntryOnContractCancel($contract->id,$contract->total);
        return $result;
    }

    private function makeTransaction(Contract $contract, float $amount, string $type = MFOPayment::TYPE_LOAN)
    {
        return MFOPayment::query()->create([
            'user_id' => $contract->user_id,
            'contract_id' => $contract->id,
            'amount' => $amount,
            'type' => $type,
            'status' => MFOPayment::STATUS_ACTIVE,
        ]);
    }

    private function repayment(Contract $contract) : array
    {
        try {
            $buyer_wallet = $this->wallet_service->getWallet($contract->user_id,WalletService::TYPE_BUYER);
            $mfo_wallet = $this->wallet_service->getWallet($contract->general_company_id,WalletService::TYPE_MFO);
            $solution_wallet = $this->wallet_service->getWallet(1,WalletService::TYPE_SOLUTION);
            $partner_wallet = $this->wallet_service->getWallet($contract->partner_id,WalletService::TYPE_PARTNER);

            //$unpaid_schedule_sum = floatval(ContractPaymentsSchedule::where('contract_id', $contract->id)->where('status', ContractPaymentsSchedule::STATUS_UNPAID)->sum('balance'));
            foreach ($contract->schedule as $schedule) {
                $schedule->balance = 0;
                $schedule->status = ContractPaymentsSchedule::STATUS_PAID;
                $schedule->paid_at = strtotime($contract->canceled_at);
                $schedule->save();
            }
            $contract->status = Contract::STATUS_COMPLETED;
            $contract->cancel_reason = 'MFO cancelled contract';
            $contract->save();

            $contract->order->status = 9;
            $contract->order->save();

            $merchant_transaction = $this->getPayment($contract->user_id,$contract->id,MFOPayment::TYPE_PAYMENT);
            $service_transaction = $this->getPayment($contract->user_id,$contract->id,MFOPayment::TYPE_SERVICE);
            $loan_transaction = $this->getPayment($contract->user_id,$contract->id,MFOPayment::TYPE_LOAN);

            $cancel_merchant_transaction = $this->makeTransaction($contract, floatval($merchant_transaction->amount), MFOPayment::TYPE_CANCEL_PAYMENT);
            $cancel_service_transaction = $this->makeTransaction($contract, floatval($service_transaction->amount), MFOPayment::TYPE_CANCEL_SERVICE);
            //$cancel_loan_transaction = $this->makeTransaction($contract, $loan_transaction->amount - $contract->balance, MFOPayment::TYPE_CANCEL_LOAN);

            $buyer_wallet->setBalance($buyer_wallet->getBalance() + floatval($loan_transaction->amount - $contract->balance));

            //update partner wallet
            $partner_wallet->setBalance($partner_wallet->getBalance() - floatval($cancel_merchant_transaction->amount));

            //update solution wallet
            $solution_wallet->setBalance($solution_wallet->getBalance() - floatval($cancel_service_transaction->amount));

            //update mfo wallet
            $mfo_wallet->setBalance($mfo_wallet->getBalance() + floatval($cancel_merchant_transaction->amount - $contract->balance));

            return ['status' => 'success', 'message' => __('billing/order.act_successfully_uploaded')];
        } catch (\Exception $exception) {
            Log::info('MFOPayment:cancelTransaction->error: ON: ' . $exception->getFile() . ' LINE: ' . $exception->getLine() . ' MESSAGE: ' . $exception->getMessage());
            return ['status' => 'error', 'message' => __('api.internal_server_error1')];
        }
    }

    private function closeContractOnThisDay(Contract $contract) : array
    {
        try {
            $contract->order->status = 5;
            $contract->order->save();

            $contract->cancel_reason = 'MFO cancelled contract';
            $contract->canceled_at = date('Y-m-d H:i:s');
            $contract->cancellation_status = 3;
            $contract->status = Contract::STATUS_CANCELED;
            $contract->save();

//            $partner_transaction = MFOPayment::where('user_id', $contract->user_id)->where('type', MFOPayment::TYPE_PAYMENT)->where('contract_id', $contract->id)->first();
            $partner_transaction = $this->getPayment($contract->user_id,$contract->id,MFOPayment::TYPE_PAYMENT);
            //$service_transaction = MFOPayment::where('user_id', $contract->user_id)->where('type', MFOPayment::TYPE_SERVICE)->where('contract_id', $contract->id)->first();
            $service_transaction = $this->getPayment($contract->user_id,$contract->id,MFOPayment::TYPE_SERVICE);
            //$loan_transaction = MFOPayment::where('user_id', $contract->user_id)->where('type', MFOPayment::TYPE_LOAN)->where('contract_id', $contract->id)->first();
            $loan_transaction = $this->getPayment($contract->user_id,$contract->id,MFOPayment::TYPE_LOAN);

            //Update wallets
            $mfo_wallet = $this->wallet_service->getWallet(3,WalletService::TYPE_MFO);
            $solution_wallet = $this->wallet_service->getWallet(1,WalletService::TYPE_SOLUTION);
            $partner_wallet = $this->wallet_service->getWallet($contract->partner_id,WalletService::TYPE_PARTNER);

            $solution_wallet->setBalance($solution_wallet->getBalance() - $service_transaction->amount);
            $partner_wallet->setBalance($partner_wallet->getBalance() - $partner_transaction->amount);
            $mfo_wallet->setBalance($mfo_wallet->getBalance() + $partner_transaction->amount);

            //SET transactions statuses canceled
            $partner_transaction->status = MFOPayment::STATUS_CANCEL;
            $partner_transaction->save();

            $service_transaction->status = MFOPayment::STATUS_CANCEL;
            $service_transaction->save();

            $loan_transaction->status = MFOPayment::STATUS_CANCEL;
            $loan_transaction->save();

            return ['status' => 'success', 'message' => __('billing/order.act_successfully_uploaded')];
        }
        catch (\Exception $exception){
            Log::info('MFOPayment:cancelTransaction->error: ON: ' . $exception->getFile() . ' LINE: ' . $exception->getLine() . ' MESSAGE: ' . $exception->getMessage());
            return ['status' => 'error', 'message' => __('api.internal_server_error1')];
        }
    }

    private function getPayment(int $user_id, int $contract_id, string $type,int $status = MFOPayment::STATUS_ACTIVE) : MFOPayment
    {
        $model = MFOPayment::query();
        if($user_id){
            $model = $model->where('user_id',$user_id);
        }
        if($contract_id){
            $model = $model->where('contract_id',$contract_id);
        }
        if($type){
            $model = $model->where('type',$type);
        }
        return $model->where('status',$status)->first();
    }
}
