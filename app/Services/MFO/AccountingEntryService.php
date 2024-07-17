<?php

namespace App\Services\MFO;

use App\Models\Account;
use App\Models\AccountingEntry;
use App\Models\AccountingEntryCBU;
use App\Models\AccountParameter;
use App\Models\AvailablePeriod;
use App\Models\Contract;
use App\Models\ContractPaymentsSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class AccountingEntryService
{
    protected static string $error_channel = 'mfo_account_errors';
    protected static string $info_channel = 'mfo_account';

    protected static array $masks = [
        //Погашение без имеющейся просроченной задолженности
        'payment_without_debt' => [
            [
                'debit_mask' => '10509',
                'is_main_debit' => true,
                'credit_mask' => '12401',
                'is_main_credit' => false,
                'd_code' => '1008',
            ],
            [
                'debit_mask' => '96345',
                'is_main_debit' => false,
                'credit_mask' => '91901',
                'is_main_credit' => false,
                'd_code' => '1008',
            ],
        ],
    ];

    /**
     * @description Выдача займа
     * @param int $contract_id
     * @return void
     */
    public function init(int $contract_id,$operation_date = null) : void
    {
        $contract = Contract::query()->find($contract_id);
        if($contract && $contract->order){
            $reverse_calc_percent = Config::get('test.mfo_reverse_calc_amount');
            $available_period = AvailablePeriod::query()->find($contract->price_plan_id);
            if($available_period){
                if($available_period->reverse_calc){
                    $amount_service = (float)$contract->total * (float)$reverse_calc_percent;
                    $amount_partner = (1 - $reverse_calc_percent) * (float)$contract->total;
                }else{
                    $amount_partner = $contract->order->partner_total - $contract->deposit;
                    $amount_service = $contract->total - $amount_partner;
                }
                Log::channel(self::$info_channel)->info('M: '.$amount_service.' N: '.$amount_partner.' PERCENT: '.$reverse_calc_percent);
                $this->createWithMask('12401','10513',$amount_partner, '1007',$contract_id,false,$operation_date,false,true);
                $this->createWithMask('12401','10513',$amount_partner, '1007',$contract_id,true,$operation_date,false,true);

                $this->createWithMask('12401','29802',$amount_service, '1007',$contract_id,false,$operation_date,false,true);
                $this->createWithMask('12401','29802',$amount_service, '1007',$contract_id,true,$operation_date,false,true);

                $this->createWithMask('91901','96345',$amount_service + $amount_partner, '1007',$contract_id,false,$operation_date,false,false);
                $this->createWithMask('91901','96345',$amount_service + $amount_partner, '1007',$contract_id,true,$operation_date,false,false);
            }
            else{
                Log::channel(self::$error_channel)->info('Entries not created for contract where ID = '.$contract_id.'. AvailablePeriod not found: price_plan_id = '.$contract->price_plan_id);
            }
        }else{
            Log::channel(self::$error_channel)->info('MFO entries not created on init: contract_exist='.(bool)$contract.' order_exist='.(bool) $contract->order);
        }
    }

    /**
     * @param string $debit_account_number
     * @param string $credit_account_number
     * @param float $amount
     * @param int $contract_id
     * @param string $destination_code
     * @param bool $is_cbu
     * @param string|null $operation_date
     * @param string|null $description
     * @return void
     */
    private function createEntry(string $debit_account_number, string $credit_account_number, float $amount, int $contract_id, string $destination_code, bool $is_cbu = false, string $operation_date = null, string $description = null) : void
    {
        $inputs = [
            'status' => AccountingEntry::STATUS_ACTIVE,
            'operation_date' => $this->isValidDate($operation_date) ? $operation_date : now(),
            'debit_account' => $debit_account_number,
            'credit_account' => $credit_account_number,
            'amount' => $amount,
            'description' => $description,
            'contract_id' => $contract_id,
            'destination_code' => $destination_code,
        ];
        $this->getAccountingEntryModel($is_cbu)->create($inputs);
        $debit_account = AccountService::getAccountModel($debit_account_number,$contract_id,null,$is_cbu);
        $credit_account = AccountService::getAccountModel($credit_account_number,$contract_id,null,$is_cbu);
        AccountService::updateBalance($debit_account,$credit_account,$amount);
    }

    /**
     * @param AccountInterface $debit_account
     * @param AccountInterface $credit_account
     * @param float $amount
     * @param int $contract_id
     * @param string $destination_code
     * @param bool $is_cbu
     * @param string|null $operation_date
     * @param string|null $description
     * @return int
     */
    private function create(AccountInterface $debit_account, AccountInterface $credit_account, float $amount, int $contract_id, string $destination_code, bool $is_cbu = false, string $operation_date = null, string $description = null,$event_id = null) : int
    {
        $inputs = [
            'status' => AccountingEntry::STATUS_ACTIVE,
            'operation_date' => $operation_date && $this->isValidDate($operation_date) ? $operation_date : now(),
            'debit_account' => $debit_account->getNumber(),
            'credit_account' => $credit_account->getNumber(),
            'amount' => $amount,
            'description' => $description,
            'contract_id' => $contract_id,
            'destination_code' => $destination_code,
            'event_id' => $event_id
        ];
        $result = $this->getAccountingEntryModel($is_cbu)->create($inputs);
        AccountService::updateBalance($debit_account,$credit_account,$amount);
        return $result->id;
    }

    private function createWithMask(string $debit_mask, string $credit_mask, float $amount, string $destination_code, int $contract_id = null, bool $is_cbu = false, string $operation_date = null,$is_bind_debit = false, $is_bind_credit = false, string $description = null, $event_id = null) : int
    {
        try {
            if($amount > 0){
                $debit_account = AccountService::getAccountModel(null,$is_bind_debit ? null : $contract_id,$debit_mask,$is_cbu)->first();
                $credit_account = AccountService::getAccountModel(null,$is_bind_credit ? null : $contract_id,$credit_mask,$is_cbu)->first();
                if($debit_account && $credit_account){
                    $inputs = [
                        'status' => AccountingEntry::STATUS_ACTIVE,
                        'operation_date' => $operation_date && $this->isValidDate($operation_date) ? $operation_date : now(),
                        'debit_account' => $debit_account->getNumber(),
                        'credit_account' => $credit_account->getNumber(),
                        'amount' => $amount,
                        'description' => $description,
                        'contract_id' => $contract_id,
                        'destination_code' => $destination_code,
                        'event_id' => $event_id
                    ];
                    $result = $this->getAccountingEntryModel($is_cbu)->create($inputs);
                    AccountService::updateBalance($debit_account,$credit_account,$amount);
                    return $result->id;
                }
                Log::channel(self::$error_channel)->info('AccountingEntryService->createWithMask->error: '.json_encode(func_get_args()));
            }
            return 0;
        }
        catch (Exception $exception){
            Log::channel(self::$error_channel)->info('AccountingEntryService->createWithMask->error: args='.json_encode(func_get_args()).'. Message: '.$exception->getMessage());
            return 0;
        }
    }

    /**
     * @description Погашение без имеющейся просроченной задолженности
     * @param int $contract_id
     * @param float $amount
     * @param string|null $operation_date
     * @return void
     */
    public function createEntryWithoutDebt(int $contract_id, float $amount, string $operation_date = null,$event_id = null) : void
    {
        $contract = Contract::query()->find($contract_id);
        if($contract){
            foreach (self::$masks['payment_without_debt'] as $mask) {
                //Account
                $this->createWithMask($mask['debit_mask'],$mask['credit_mask'],$amount,$mask['d_code'], $contract_id,false,$operation_date,$mask['is_main_debit'],$mask['is_main_credit'],null,$event_id);

                //AccountCBU
                $this->createWithMask($mask['debit_mask'],$mask['credit_mask'],$amount,$mask['d_code'], $contract_id,true,$operation_date,$mask['is_main_debit'],$mask['is_main_credit'],null,$event_id);
            }
            // 2-Entry (calculate charges and percent)
            $account_16307 = AccountService::getAccountModel(null,$contract->id,'16307',false)->first();
            $account_16307_cbu = AccountService::getAccountModel(null,$contract->id,'16307',true)->first();
            if($account_16307){
                $this->createWithMask('10509','16307',$account_16307->getBalance(),'0000',$contract->id,false,now(),true,false,null,$event_id);
            }
            if($account_16307_cbu){
                $this->createWithMask('10509','16307',$account_16307_cbu->getBalance(),'0000',$contract->id,true,now(),true,false,null,$event_id);
            }
        }
    }

    /**
     * @description выход платежа в просрочку
     * @param int $contract_id
     * @param string|null $operation_date
     * @return void
     */
    public function createEntryWithDelay(ContractPaymentsSchedule $schedule, string $operation_date = null) : bool
    {
        $mask = [
            'debit_mask' => '12405',
            'is_main_debit' => false,
            'credit_mask' => '12401',
            'is_main_credit' => false,
            'd_code' => '1009',
        ];
        //Account
        $id = $this->createWithMask($mask['debit_mask'],$mask['credit_mask'],$schedule->balance,$mask['d_code'], $schedule->contract_id,false,$operation_date,$mask['is_main_debit'],$mask['is_main_credit']);
        $schedule->account_entry_id = $id;
        $schedule->save();
        //AccountCBU
        $debit_acc_cbu = AccountService::getAccountModel(null,$mask['is_main_debit'] ? null : $schedule->contract_id,$mask['debit_mask'],true)->first();
        $credit_acc_cbu = AccountService::getAccountModel(null,$mask['is_main_credit'] ? null : $schedule->contract_id,$mask['credit_mask'],true)->first();
        if($debit_acc_cbu && $credit_acc_cbu){
            if($debit_acc_cbu->getBalance() > 0){
                $this->create($credit_acc_cbu,$debit_acc_cbu,$debit_acc_cbu->getBalance(),$schedule->contract_id,$mask['d_code'],true,$operation_date,null,$schedule->id);
            }
            $this->create($debit_acc_cbu,$credit_acc_cbu,$credit_acc_cbu->getBalance(),$schedule->contract_id,$mask['d_code'],true,$operation_date,null,$schedule->id);
            return true;
        }
        Log::channel(self::$error_channel)->info('AccountingEntryService->createEntryWithDelay->error: '.func_num_args());
        return false;
    }

    /**
     * @description созданию резервов
     * @param int $contract_id
     * @param string|null $operation_date
     * @return bool
     */
    public function createEntryReserve(int $contract_id, string $operation_date = null) : bool
    {
        $contract = Contract::query()->find($contract_id);
        if($contract){
            $mask = [
                'debit_mask' => '56802',
                'is_main_debit' => true,
                'credit_mask' => '12499',
                'is_main_credit' => false,
                'd_code' => '1012',
            ];
            $percent = $this->calculateReservePercent($contract->expired_days);
            if($percent){
                //Account
                $amount = $this->calculateReserveBalance($contract);
                $this->createWithMask($mask['debit_mask'],$mask['credit_mask'],$amount,$mask['d_code'], $contract_id,false,$operation_date,$mask['is_main_debit'],$mask['is_main_credit']);

                //AccountCBU
                $amount_cbu = $this->calculateReserveBalance($contract,true);
                $this->createWithMask($mask['debit_mask'],$mask['credit_mask'],$amount_cbu,$mask['d_code'], $contract_id,true,$operation_date,$mask['is_main_debit'],$mask['is_main_credit']);
                return true;
            }
        }
        return false;
    }

    /**
     * @description Погашение при имеющейся задолженности
     * @param int $contract_id
     * @param float $amount
     * @param string|null $operation_date
     * @return void
     */
    public function createEntryWithDebt(int $contract_id, float $amount, string $operation_date = null, $event_id = null) : void
    {
        try {
            $contract = Contract::query()->find($contract_id);
            if($contract){
                // 1-Entry
                $debit_acc = AccountService::getAccountModel(null,null,'10509')->first();
                $credit_acc = AccountService::getAccountModel(null,$contract_id,'12405')->first();
                //Если сумма платежа больше суммы текущего баланса (12405), то необходимо дополнительно создать проводку
                $overdue_amount = 0;
                if($credit_acc->getBalance() < $amount){
                    $overdue_amount = $amount - $credit_acc->getBalance();
                }
                $this->create($debit_acc,$credit_acc,$overdue_amount ? $credit_acc->getBalance() : $amount,$contract_id,'1008',false,$operation_date);
                if($overdue_amount){
                    $credit = AccountService::getAccountModel(null,$contract_id,'12401')->first();
                    $this->create($debit_acc,$credit,$overdue_amount,$contract_id,'1008',false,$operation_date);
                }

                // 2-Entry
                $this->createWithMask('96345','91901',$amount,'1008', $contract_id,false,$operation_date);

                //Check reserve account balance
                $reserve_debit_acc = AccountService::getAccountModel(null,$contract_id,'12499',false)->first();
                if($reserve_debit_acc && $reserve_debit_acc->getBalance() > 0){
                    $reserve_credit_acc = AccountService::getAccountModel(null,null,'56802',false)->first();
                    $reserve_account_balance = ContractPaymentsSchedule::query()
                        ->where('contract_id','=',$contract_id)
                        ->where('payment_date','<',now())
                        ->where('status','=',ContractPaymentsSchedule::STATUS_UNPAID)
                        ->where('balance','>',0)
                        ->sum('balance');
                    $total_amount = $reserve_debit_acc->getBalance();
                    if($reserve_account_balance > 0){
                        $percent = $this->calculateReservePercent($contract->expired_days);
                        if($percent) {
                            $total_amount = $this->calculateReserveBalance($contract,false);
                        }
                    }
                    $this->create($reserve_debit_acc,$reserve_credit_acc,$total_amount,$contract_id,'1008',false,$operation_date);
                }

                $this->createEntryWithDebtCBU($contract_id,$amount,$operation_date);

                // 3-Entry (calculate charges and percent)
                $account_16307 = AccountService::getAccountModel(null,$contract->id,'16307',false)->first();
                $account_16307_cbu = AccountService::getAccountModel(null,$contract->id,'16307',true)->first();
                if($account_16307){
                    $this->createWithMask('10509','16307',$account_16307->getBalance(),'0000',$contract->id,false,now(),true,false);
                }
                if($account_16307_cbu){
                    $this->createWithMask('10509','16307',$account_16307_cbu->getBalance(),'0000',$contract->id,true,now(),true,false);
                }
            }
        }
        catch (Exception $exception){
            Log::channel(self::$error_channel)->info('createEntryWithDebt->error: '.$exception->getMessage());
        }
    }

    public function createEntryOnContractCancel(int $contract_id, float $amount)
    {
        $contract = Contract::query()->find($contract_id);
        if($contract){
            if(date('Y-m-d',strtotime($contract->confirmed_at)) == date('Y-m-d')){
                $this->closeContractOnThisDay($contract_id);
            }
            else{
                //Account
                $this->closeContractOnAnotherDay($contract_id,$amount,false);
                //AccountCBU
                $this->closeContractOnAnotherDay($contract_id,$amount,true);
                //Close accounts
                if((float)$contract->total == $amount){
                    AccountService::closeAccounts($contract_id,false);
                    AccountService::closeAccounts($contract_id,true);
                }
            }
        }
    }

    private function closeContractOnThisDay(int $contract_id)
    {
        // Change entry statuses to 0
        $this->getAccountingEntryModel(false)->where('contract_id',$contract_id)->update(['status' => 0]);
        $this->getAccountingEntryModel(true)->where('contract_id',$contract_id)->update(['status' => 0]);
        //Set account balances and statuses to 0
        AccountService::getAccountModel(null,$contract_id)->update(['balance' => 0,'status' => Account::STATUS_CLOSE]);
        AccountService::getAccountModel(null,$contract_id,null,true)->update(['balance' => 0,'status' => Account::STATUS_CLOSE]);
        //Update balance 10513
        $contract = Contract::query()->find($contract_id);

        $account = AccountService::getAccountModel(null,null,'10513')->first();
        $account->balance += $contract->total;
        $account->save();

        $account_cbu = AccountService::getAccountModel(null,null,'10513',true)->first();
        $account_cbu->balance += $contract->total;
        $account_cbu->save();
    }

    private function closeContractOnAnotherDay(int $contract_id, float $amount,bool $is_cbu = false) : bool
    {
        //1-Entry
        $account_12401 = AccountService::getAccountModel(null, $contract_id,'12401',$is_cbu)->first();
        $account_12405 = AccountService::getAccountModel(null, $contract_id,'12405',$is_cbu)->first();
        //Если сумма отмененных товаров больше суммы текущих балансов по счетам 12401 и 12405
        if($amount > ($account_12405->getBalance() + $account_12401->getBalance())){
            $first_entry_amount = $account_12401->getBalance();
        }
        //Если сумма отмененных товаров меньше или равна сумме текущих балансов по счетам 12401 и 12401
        else{
            //текущий баланс счета 12401 * (сумма отмененных товаров/сумма балансов по счетам 12401 и 12401)
            $first_entry_amount = $account_12401->getBalance() * ($amount / ($account_12401->getBalance() + $account_12405->getBalance()));
        }
        $this->createWithMask('10509','12401',$first_entry_amount,'1008', $contract_id,$is_cbu,null,true,false);

        //2-Entry
        //Если сумма отмененных товаров больше суммы текущих балансов по счетам 12401 и 12405
        if($amount > ($account_12405->getBalance() + $account_12401->getBalance())){
            $second_entry_amount = $account_12405->getBalance();
        }
        //Если сумма отмененных товаров меньше или равна сумме текущих балансов по счетам 12401 и 12405
        else{
            //текущий баланс счета 12405 * (сумма отмененных товаров/сумма балансов по счетам 12401 и 12405)
            $second_entry_amount = $account_12405->getBalance() * ($amount / ($account_12401->getBalance() + $account_12405->getBalance()));
        }
        $this->createWithMask('10509','12405',$second_entry_amount,'1008', $contract_id,$is_cbu,null,true,false);

        //3-Entry: Если счет с маской 12499, привязанный к договору имеет ненулевой баланс
        $account_12499 = AccountService::getAccountModel(null,$contract_id,'12499',$is_cbu)->first();
        if($account_12499->getBalance() == 0){
            $summa = $account_12499->getBalance();
            $schedules_balance = ContractPaymentsSchedule::query()
                ->where('contract_id','=',$contract_id)
                ->where('payment_date','<',now())
                ->where('status','=',ContractPaymentsSchedule::STATUS_UNPAID)
                ->where('balance','>',0)
                ->sum('balance');
            if($schedules_balance > 0){
                $contract = Contract::query()->find($contract_id);
                $summa = $amount - $this->calculateReserveBalance($contract,$is_cbu);
            }
            $this->createWithMask('12499','56802',$summa,'1008',$contract_id,$is_cbu,null,false,true);
            //4-Entry
            $account_91901 = AccountService::getAccountModel(null,$contract_id,'91901',$is_cbu)->first();
            if($account_91901){
                $this->createWithMask('96345','91901',$account_91901->getBalance(),'1008',$contract_id,$is_cbu,null,false,false);
            }
        }
        return true;
    }

    private function createEntryWithDebtCBU(int $contract_id, float $amount, string $operation_date = null)
    {
        $contract = Contract::query()->find($contract_id);
        if($contract){
            // 1-Entry
            $this->createWithMask('10509','12405',$amount,'1008', $contract_id,true,null,true,false);

            //Если в таблице contract_payments_schedule отсутствуют просроченные платежи с ненулевым балансом
            $schedule_balance_with_debt = ContractPaymentsSchedule::query()
                ->where('contract_id','=',$contract_id)
                ->where('payment_date','<',now())
                ->where('status','=',ContractPaymentsSchedule::STATUS_UNPAID)
                ->where('balance','>',0)
                ->sum('balance');
            if($schedule_balance_with_debt == 0){
                $d_account = AccountService::getAccountModel(null,$contract_id,'12401',true)->first();
                $c_account = AccountService::getAccountModel(null,$contract_id,'12405',true)->first();
                $this->create($d_account,$c_account,$c_account->getBalance(),$contract_id,'1008',true);

                //Если текущий баланс по счету 12499 не равен нулю
                $account_12499 = AccountService::getAccountModel(null,$contract_id,'12499',true)->first();
                if($account_12499->getBalance() > 0){
                    $cr_acc = AccountService::getAccountModel(null,null,'56802',true)->first();
                    $this->create($account_12499,$cr_acc,$account_12499->getBalance(),$contract_id,'1008',true);
                }
            }
            //Если в таблице contract_payments_schedule существуют просроченные платежи с ненулевым балансом
            if($schedule_balance_with_debt > 0){
                $this->createWithMask('12499','56802',$this->calculateReserveBalance($contract,true),'1008', $contract_id,true,null,false,true);
            }
            $this->createWithMask('96345','91901',$amount,'1008', $contract_id,true,null,false, false);
        }
    }

    /**
     * @param string $account_number
     * @param bool $is_cbu
     * @return bool
     * @throws Exception
     */
    public function closeEntry(string $account_number, bool $is_cbu = false) : bool
    {
        $account = $this->getAccountModel($is_cbu)->where('number',$account_number)->first();
        if(!$account){
            throw new \Exception('Account not found');
        }
        if((float) $account->balance <> 0){
            throw new \Exception('Закрытие счета с ненулевым балансом невозможно');
        }
        if($account->status == Account::STATUS_CLOSE){
            throw new \Exception('Счет уже закрыт');
        }
        $account->status = Account::STATUS_CLOSE;
        $account->closed_at = now();
        $account->save();
        return true;
    }

    /**
     * @param bool $is_cbu
     * @return Builder
     */
    public function getAccountingEntryModel(bool $is_cbu) : Builder
    {
        if($is_cbu){
            $model = AccountingEntryCBU::query();
        }else{
            $model = AccountingEntry::query();
        }
        return $model;
    }

    /**
     * @param string|null $date
     * @return bool
     */
    public static function isValidDate(string $date) : bool
    {
        return date('Y-m-d H:i:s', strtotime($date)) == $date;
    }

    private function calculateReserveBalance(Contract $contract,bool $is_cbu = false) : float
    {
        $balance = AccountService::getAccountModel(null, $contract->id, null, $is_cbu)->whereIn('mask', ['12401', '12405'])->sum('balance');
        return round((float)$balance * $this->calculateReservePercent($contract->expired_days),2);
    }

    private function calculateReservePercent(int $expired_days) : float
    {
        $percent = 0;
        if ($expired_days == 31 || $expired_days == 61){
            $percent = 0.25;
        }
        if ($expired_days == 91){
            $percent = 0.50;
        }
        return $percent;
    }

    public function closeAccountsAndEntries(int $contract_id, string $operation_date = null) : bool
    {
        try {
            $operation_date = $operation_date && $this->isValidDate($operation_date) ? $operation_date : now();
            //Set account balances and statuses to 0
            AccountService::getAccountModel(null,$contract_id)
                                    ->where('balance','=',0)
                                    ->where('status','=',Account::STATUS_OPEN)
                                    ->update(['status' => Account::STATUS_CLOSE,'closed_at' => $operation_date]);

            AccountService::getAccountModel(null,$contract_id,null,true)
                                    ->where('status','=',Account::STATUS_OPEN)
                                    ->where('balance','=',0)
                                    ->update(['status' => Account::STATUS_CLOSE,'closed_at' => $operation_date]);
            Log::channel(self::$info_channel)->info("Closed accounts and entries where contract_id = $contract_id");
            return true;
        }
        catch (Exception $exception){
            Log::channel(self::$error_channel)->info($exception->getMessage());
            return false;
        }
    }

    public function calculateAndChargePercent(Contract $contract,$is_cbu = false) : bool
    {
        try {
            //1. Entry (Если текущий день является днем платежа)
            //$schedule = ContractPaymentsSchedule::query()
            //                                    ->whereDate('payment_date','=',DB::raw('CURDATE()'))
            //                                    ->where('status','=',0)
            //                                    ->where('contract_id','=',$contract->id)
            //                                    ->first();
            //if($schedule){
            //    $account_16307 = AccountService::getAccountModel(null,$contract->id,'16307',$is_cbu)->first();
            //    if($account_16307){
            //        $this->createWithMask('10509','16307',$account_16307->getBalance(),'0000',$contract->id,$is_cbu,now(),true,false);
            //    }
            //}
            //2. Entry
            $debit_account_mask = '16307';
            $credit_account_mask = '42001';
            $account_12405 = AccountService::getAccountModel(null,$contract->id,'12405',$is_cbu)->first();
            $account_12401 = AccountService::getAccountModel(null,$contract->id,'12401',$is_cbu)->first();
            $period = $contract->price_plan;
            if($account_12405 && $account_12401 && $period){
                $days = Carbon::now()->isLeapYear() ? 366 : 365;
                if($account_12405->getBalance() > 0){
                    $credit_account_mask = '42005';
                }
                $amount = ($account_12405->getBalance() + $account_12401->getBalance()) * ($period->interest_rate / $days) / 100;
                if($amount < 0.01){
                    $amount = 0.01;
                }
                $this->createWithMask($debit_account_mask,$credit_account_mask,$amount,'0000',$contract->id,$is_cbu,now(),false,true);
                $this->createWithMask('10509','16307',$amount,'0000',$contract->id,$is_cbu,now()->addSecond(),true,false);
            }
            return true;
        }
        catch (Exception $exception){
            Log::channel(self::$error_channel)->info('calculateAndChargePercent->error: '.$exception->getMessage());
            return false;
        }
    }

    public function resolveInvalidContracts(Contract $contract,float $entry_amount,$is_cbu = false)
    {
        $account_12405 = AccountService::getAccountModel(null,$contract->id,'12405',$is_cbu)->first();
        if($account_12405){
            if($account_12405->getBalance() > 0){
                $this->createWithMask('10509','12405',$account_12405->getBalance(),'1008',$contract->id,$is_cbu,'2023-03-31 01:00:00',true,false);
                $this->updateHistory('10509',$account_12405->getBalance(),null,$is_cbu,'debit');
                $this->updateHistory('12405',$account_12405->getBalance(),$contract->id,$is_cbu,'credit');

                $this->createWithMask('10509','12401',$entry_amount - $contract->total - $account_12405->getBalance(),'1008',$contract->id,$is_cbu,'2023-03-31 01:00:00',true,false);
                $this->updateHistory('10509',$entry_amount - $contract->total - $account_12405->getBalance(),null,$is_cbu,'debit');
                $this->updateHistory('12401',$entry_amount - $contract->total - $account_12405->getBalance(),$contract->id,$is_cbu,'credit');

                $this->createWithMask('96345','91901',$entry_amount - $contract->total,'1008',$contract->id,$is_cbu,'2023-03-31 01:00:00',false,false);
                $this->updateHistory('96345',$entry_amount - $contract->total,$contract->id,$is_cbu,'debit');
                $this->updateHistory('91901',$entry_amount - $contract->total,$contract->id,$is_cbu,'credit');
            }else{
                $this->createWithMask('10509','12401',$entry_amount - $contract->total,'1008',$contract->id,$is_cbu,'2023-03-31 01:00:00',true,false);
                $this->updateHistory('10509',$entry_amount - $contract->total,null,$is_cbu,'debit');
                $this->updateHistory('12401',$entry_amount - $contract->total,$contract->id,$is_cbu,'credit');

                $this->createWithMask('96345','91901',$entry_amount - $contract->total,'1008',$contract->id,$is_cbu,'2023-03-31 01:00:00',false,false);
                $this->updateHistory('96345',$entry_amount - $contract->total,$contract->id,$is_cbu,'debit');
                $this->updateHistory('91901',$entry_amount - $contract->total,$contract->id,$is_cbu,'credit');
            }
        }
    }

    public function resolveInvalidContractsForCbu(Contract $contract,float $entry_amount,$is_cbu = true)
    {
        $account_12405 = AccountService::getAccountModel(null,$contract->id,'12405',$is_cbu)->first();
        if($account_12405){
            if($account_12405->getBalance() > 0){
                $this->createWithMask('10509','12405',$entry_amount - $contract->total,'1008',$contract->id,$is_cbu,'2023-03-31 01:00:00',true,false);
                $this->updateHistory('10509',$entry_amount - $contract->total,null,$is_cbu,'debit');
                $this->updateHistory('12405',$entry_amount - $contract->total,$contract->id,$is_cbu,'credit');

                $this->createWithMask('96345','91901',$entry_amount - $contract->total,'1008',$contract->id,$is_cbu,'2023-03-31 01:00:00',false,false);
                $this->updateHistory('96345',$entry_amount - $contract->total,$contract->id,$is_cbu,'debit');
                $this->updateHistory('91901',$entry_amount - $contract->total,$contract->id,$is_cbu,'credit');
            }else{
                $this->createWithMask('10509','12401',$entry_amount - $contract->total,'1008',$contract->id,$is_cbu,'2023-03-31 01:00:00',true,false);
                $this->updateHistory('10509',$entry_amount - $contract->total,null,$is_cbu,'debit');
                $this->updateHistory('12401',$entry_amount - $contract->total,$contract->id,$is_cbu,'credit');

                $this->createWithMask('96345','91901',$entry_amount - $contract->total,'1008',$contract->id,$is_cbu,'2023-03-31 01:00:00',false,false);
                $this->updateHistory('96345',$entry_amount - $contract->total,$contract->id,$is_cbu,'debit');
                $this->updateHistory('91901',$entry_amount - $contract->total,$contract->id,$is_cbu,'credit');
            }
        }
    }

    private function updateHistory(string $mask, float $amount, int $contract_id = null, $is_cbu = false,string $type = 'debit')
    {
        $debit_account = AccountService::getAccountModel(null,$contract_id,$mask,$is_cbu)->first();
        if($debit_account){
            $history_service = new AccountBalanceHistoryService();
            $debit_account_param = AccountParameter::query()->where('mask',$mask)->first();
            $debit_histories = $history_service->getModel($is_cbu)->where('account_id','=',$debit_account->id)->where('operation_date','>','2023-03-31 01:00:00')->get();
            if($debit_histories){
                foreach ($debit_histories as $debit_history) {
                    $debit_account_balance = AccountService::calculateBalanceAmount($debit_history->balance,$amount,$debit_account_param->type,$type);
                    $debit_history->balance = $debit_account_balance;
                    $debit_history->save();
                }
            }
        }
    }

    public function partialCancellation(int $contract_id,float $amount,bool $is_cbu) : array
    {
        $balance12405 = AccountService::getAccountModel(null,$contract_id,'12405')->first();
        if($balance12405){
            if($balance12405->getBalance() > 0){
                return ['status' => 'error','message' => ['Access denied to cancel contract (balance)']];
            }
            $this->createWithMask('77777','12401',$amount,'1008',$contract_id,$is_cbu,now(),true,false);
            $this->createWithMask('96345','91901',$amount,'1008',$contract_id,$is_cbu,now(),false,false);
        }
        return [];
    }
}
