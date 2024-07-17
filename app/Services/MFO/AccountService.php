<?php

namespace App\Services\MFO;

use App\Models\Account;
use App\Models\AccountCBU;
use App\Models\AccountParameter;
use App\Models\Buyer;
use App\Models\Contract;
use App\Models\Currency;
use App\Models\GeneralCompany;
use App\Models\User;
use App\UserNibbd;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class AccountService
{
    protected static string $error_channel = 'mfo_account_errors';
    protected static string $info_channel = 'mfo_account';
    protected static array $main_accounts = ['10513','10509'];

    /**
     * @param int $contract_id
     * @return void
     */
    public function init(int $contract_id) : void
    {
        $contract = Contract::query()->find($contract_id);
        if($contract){
            $account_parameters = AccountParameter::query()->where('contract_bind','=',1)->get();
            if(count($account_parameters) > 0){
                foreach ($account_parameters as $item) {
                    try {
                        $this->createAccount($contract_id,$item->mask);
                        Log::channel(self::$info_channel)->info("Account created: contract: $contract_id, user_id: $contract->user_id, mask: $item->mask");
                    }
                    catch (\Exception $exception){
                        Log::channel(self::$error_channel)->info($exception->getMessage());
                    }
                }
            }
        }
    }

    /**
     * @param int $contract_id
     * @param string $mask
     * @param string $currency_number
     * @return void
     * @throws \Exception
     */
    public function createAccount(int $contract_id, string $mask, string $currency_number = '000') : void
    {
        $contract = Contract::with('buyer')->find($contract_id);
        $parameters = AccountParameter::query()->where('mask',$mask)->first();
        $general_company = GeneralCompany::query()->find($contract->general_company_id);
        $currency = Currency::query()->where('number',$currency_number)->first();
        if(!$currency){
            throw new \Exception('Invalid currency number provided');
        }
        if(!$contract){
            throw new \Exception('Contract not found');
        }
        if(Account::query()->where('contract_id',$contract_id)->where('mask',$mask)->exists()){
            throw new \Exception('Account where contract = '.$contract_id.' and mask = '.$mask.' already exists');
        }
        $inputs = [
            'user_id' => $contract->user_id,
            'contract_id' => $contract->id,
            'status' => Account::STATUS_OPEN,
            'name' => $parameters ? $parameters->name : null,
            'number' => $this->generateAccountNumber($general_company->nibbd,$contract->user_id,$mask,$currency_number,$this->getBuyerNIBBD($contract->buyer)),
            'balance' => 0,
            'mask' => $mask,
            'currency' => $currency->code,
        ];
        self::getAccountModel()->create($inputs);
        self::getAccountModel(null,null,null,true)->create($inputs);
    }

    /**
     * @param AccountInterface $debit_account
     * @param AccountInterface $credit_account
     * @param float $amount
     * @return void
     */
    public static function updateBalance(AccountInterface $debit_account, AccountInterface $credit_account, float $amount)
    {
        $debit_account_param = AccountParameter::query()->where('mask',$debit_account->getMask())->first();
        $debit_account->updateBalance(self::calculateBalanceAmount($debit_account->getBalance(),$amount,$debit_account_param->type));
        $credit_account_parameter = AccountParameter::query()->where('mask',$credit_account->getMask())->first();
        $credit_account->updateBalance(self::calculateBalanceAmount($credit_account->getBalance(),$amount,$credit_account_parameter->type,'credit'));
    }

    /**
     * @param string|null $number
     * @param int|null $contract_id
     * @param string|null $mask
     * @param bool $is_cbu
     * @return Builder
     */
    public static function getAccountModel(string $number = null, int $contract_id = null, string $mask = null, bool $is_cbu = false) : Builder
    {
        if($is_cbu){
            $model = AccountCBU::query();
        }else{
            $model = Account::query();
        }
        if($number){
            $model = $model->where('number',$number);
        }
        if($mask){
            $model = $model->where('mask',$mask);
        }
        if($contract_id){
            $model = $model->where('contract_id',$contract_id);
        }
        return $model;
    }

    /**
     * @param float $account_balance
     * @param float $amount
     * @param int $balance_type
     * @param string $type
     * @return float
     */
    public static function calculateBalanceAmount(float $account_balance, float $amount, int $balance_type = AccountParameter::TYPE_ACTIVE, string $type = 'debit') : float
    {
        if($balance_type == AccountParameter::TYPE_ACTIVE && $type == 'debit'){
            $account_balance = $account_balance + $amount;
        }
        if($balance_type == AccountParameter::TYPE_INACTIVE && $type == 'debit'){
            $account_balance = $account_balance - $amount;
        }
        if($balance_type == AccountParameter::TYPE_ACTIVE && $type == 'credit'){
            $account_balance = $account_balance - $amount;
        }
        if($balance_type == AccountParameter::TYPE_INACTIVE && $type == 'credit'){
            $account_balance = $account_balance + $amount;
        }
        return $account_balance;
    }

    /**
     * @param string $mfo_nibbd
     * @param int $user_id
     * @param string $mask
     * @param string $currency_code
     * @param string $user_nibbd
     * @return string
     */
    private function generateAccountNumber(string $mfo_nibbd, int $user_id, string $mask, string $currency_code, string $user_nibbd) : string
    {
        $index_number = $this->getIndexNumber($mask,$user_id);
        $control_key = $this->calculateControlKey($mfo_nibbd.$mask.$currency_code.$user_nibbd.$index_number);
        return $mask.$currency_code.$control_key.$user_nibbd.$index_number;
    }

    /**
     * @param string $mask
     * @param int $user_id
     * @return string
     */
    private function getIndexNumber(string $mask, int $user_id) : string
    {
        $index_number = '001';
        $account = Account::query()->where('mask',$mask)->where('user_id',$user_id)->orderBy('id','DESC')->first();
        if($account){
            $index_number = substr($account->number,-3);
            if((int) $index_number < 999){
                $index_number = str_pad((int)$index_number + 1,3,'0',STR_PAD_LEFT);
            }
        }
        return $index_number;
    }

    /**
     * @param string $keys
     * @return int
     */
    public function calculateControlKey(string $keys) : int
    {
        $sum = 0;
        //Step 1
        for($i = 0; $i < strlen($keys); $i++){
            if(strlen($keys) == $i + 1) {
                $sum += (int)$keys[$i] * strlen($keys);
                continue;
            }
            $sum += (int)$keys[$i] * (int)$keys[$i + 1];
        }
        //Step 2
        $x = floor(fmod($sum,11));
        //Step 3
        if($x == 0){
            $x = 9;
        }
        elseif($x == 1){
            $x = 0;
        }
        else{
            $x = floor(abs(11 - $x));
        }
        return $x;
    }

    /**
     * @param Buyer $buyer
     * @return string
     */
    private function getBuyerNIBBD(Buyer $buyer) : string
    {
        if(!$buyer->nibbd){
            self::generateNIBBDForBuyer($buyer);
        }
        return $buyer->nibbd;
    }

    /**
     * @param Buyer $buyer
     * @return void
     */
    public static function generateNIBBDForBuyer(Buyer $buyer) : void
    {
        if(!$buyer->nibbd){
            try {
                    $result = UserNibbd::query()->create(['user_id' => $buyer->id]);
                    if($result){
                        $buyer->nibbd = $result->id;
                        $buyer->save();
                    }
                }
            catch (\Exception $exception)
            {
                Log::channel(self::$error_channel)->info('AccountService->generateNIBBDForBuyer->error '.$exception->getMessage());
            }
        }
    }

    public static function closeAccounts(int $contract_id,$is_cbu) : bool
    {
        self::getAccountModel(null,$contract_id,null, $is_cbu)->update(['status' => 0]);
        self::getAccountModel(null,$contract_id,null, $is_cbu)->update(['status' => 0]);
        return true;
    }
}
