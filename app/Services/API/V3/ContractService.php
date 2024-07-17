<?php

namespace App\Services\API\V3;

use App\Helpers\FileHelper;
use App\Helpers\SellerBonusesHelper;
use App\Http\Requests\ContractCancelRequest;
use App\Models\AutopayDebitHistory;
use App\Models\Buyer;
use App\Models\BuyerSetting;
use App\Models\CancelContract;
use App\Models\CollectCost;
use App\Models\Contract;
use App\Models\File;
use App\Models\GeneralCompany;
use App\Models\Payment;
use App\Models\Role;
use App\Services\MFO\MFOPaymentService;
use App\Traits\UzTaxTrait;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;
use Validator;

class ContractService extends BaseService
{
    public static function validateSignContract(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
            'sign' => 'required|file'
        ]);
        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function signContract(Request $request)
    {

        $user = Auth::user();
        $inputs = self::validateSignContract($request);
        $is_vendor = BuyerService::is_vendor($user->role_id);
        if($is_vendor && !$request->has('buyer_id')){
            return self::handleError([__('api.bad_request')]);
        }
        if($is_vendor){
            $buyer = Buyer::find($request->buyer_id);
        }else{
            $buyer = Buyer::find($user->id);
        }
        if(!$buyer){
            return self::handleError([__('auth.error_user_not_found')]);
        }
        $signFile = $request->file('sign');
        $contract = Contract::where('user_id', $buyer->id)->find($inputs['id']);
        if(!$contract){
            return self::handleError([__('api.contract_not_found')]);
        }
        if($is_vendor && $contract->partner_id !== $user->id){
            return self::handleError([__('api.contract_not_found')]);
        }
        if (!$contract->is_allowed_online_signature) {
            return self::handleError([__('api.bad_request')]);
        }
        try {
            $signParams = [
                'files' => [File::TYPE_SIGNATURE => $signFile],
                'element_id' => $contract->id,
                'model' => 'contract'
            ];
            FileHelper::upload($signParams, [], true);
            $path = 'contract/' . $contract->id . '/';
            $langCode = $request->language_code ?: '';

            //Getting general sign path
            if (!empty($contract->general_company_id)) {
                $generalCompanyRecord = GeneralCompany::find($contract->general_company_id);
                if(isset($generalCompanyRecord)) {
                    $generalSign = $generalCompanyRecord->sign;
                }
            }

            $data =  [
                'order' => $contract->order,
                'signPath' => FileHelper::url($contract->signature->path),
                'generalSignPath' => $generalSign ?? ''
            ];

            FileHelper::generateAndUploadHtml($contract->id, 'contract', File::TYPE_SIGNED_CONTRACT, $langCode, $path, 'billing.order.parts.account_pdf',$data);
        } catch (\Exception $e) {
            return self::handleError([$e->getMessage()]);
        }
        return self::handleResponse(['link' => FileHelper::url($contract->signedContract->path),]);
    }

    public static function cancelContract(ContractCancelRequest $request)
    {
        try {
            $user = auth()->user();
            if (!$user->hasRole('admin')) {
                throw new Exception(__('app.err_access_denied_role'), 403);
            }
            $contract = Contract::with(['order', 'buyer.settings', 'price_plan'])->find($request->get('contract_id'));
            if (!in_array($contract->status, [1, 3, 4], true)) {
                throw new Exception('Неверный статус контракта', 400);
            }
            //TODO Logic ctrl+c & ctrl+v from Core\ContractController (just added return to MINI_BALANCE)
            $contract->update(['status' => 5, 'cancellation_status' => 3, 'canceled_at' => Carbon::now()]);
            $contract->order->update(['status' => 5]);
            if (isset($contract->price_plan) && $contract->price_plan->is_mini_loan == 1) {
                $contract->buyer->settings->mini_balance += ($contract->order->credit - $contract->deposit);
            } else {
                $contract->buyer->settings->balance += ($contract->order->credit - $contract->deposit);
            }
            $contract->buyer->settings->personal_account += $contract->deposit;
            $contract->buyer->settings->save();
            if ($contract->schedule[0]->status == 1) {
                $confirmed_at = Carbon::parse($contract->confirmed_at)->format('dm');
                $paid_at      = Carbon::parse($contract->schedule[0]->paid_at)->format('dm');
                if ($confirmed_at === $paid_at) {
                    $contract->buyer->settings->personal_account += $contract->schedule[0]->total;
                    $payment = new Payment;
                    $payment->schedule_id = $contract->schedule[0]->id;
                    $payment->type = 'refund';
                    $payment->order_id = $contract->order_id;
                    $payment->contract_id = $contract->id;
                    $payment->amount = -1 * $contract->schedule[0]->total;
                    $payment->user_id = $contract->buyer->id;
                    $payment->payment_system = 'ACCOUNT';
                    $payment->status = 1;
                    $payment->save();
                }
            }

            $cancelContract              = new CancelContract();
            $cancelContract->contract_id = $contract->id;
            $cancelContract->user_id     = $contract->user_id;
            $cancelContract->created_at  = $contract->canceled_at;
            $cancelContract->canceled_at = $contract->canceled_at;
            $cancelContract->total       = -1 * $contract->total;
            $cancelContract->balance     = -1 * $contract->balance;
            $cancelContract->deposit     = -1 * $contract->deposit;
            $cancelContract->save();

            if($contract->general_company_id === GeneralCompany::MFO_COMPANY_ID) {
                $service = new MFOPaymentService();
                $service->cancelTransactionCheckSms($contract);
            }

            SellerBonusesHelper::refundByContract($contract->id);
            UzTaxTrait::refundReturnProduct($contract->id);
        } catch (Throwable|Exception $e) {
            return self::handleError([$e->getMessage()]);
        }

        return self::handleResponse(['status' => 'success']);
    }
}
