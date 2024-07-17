<?php

namespace App\Services\MFO;

use App\Models\Account;
use App\Models\AccountBalanceHistory;
use App\Models\AccountBalanceHistoryCBU;
use App\Models\AccountCBU;
use App\Models\Contract;
use Illuminate\Support\Facades\Log;

class AccountBalanceHistoryService
{
    public function init($is_cbu = false) : array
    {
        $success = 0;
        $fails = 0;
        $accounts = AccountService::getAccountModel(null,null,null,$is_cbu)->where('status','=',Account::STATUS_OPEN)->get();
        foreach ($accounts as $account){
            $res = $this->getModel($is_cbu)->create([
                'account_id' => $account->id,
                'operation_date' => now(),
                'balance' => $account->balance,
            ]);
            $res ? $success ++ : $fails++;
        }
        return ['total' => count($accounts),'success' => $success,'fails' => $fails];
    }

    public function getModel(bool $is_cbu = true)
    {
        if($is_cbu){
            $model = AccountBalanceHistoryCBU::query();
        }else{
            $model = AccountBalanceHistory::query();
        }
        return $model;
    }

    /**
     * @param string $account_number
     * @param string|null $operation_date
     * @return void
     * @throws \Exception
     */
    private function createHistory(string $account_number, string $operation_date = null) : void
    {
        $account = Account::query()->where('number',$account_number)->first();
        $account_cbu = AccountCBU::query()->where('number',$account_number)->first();
        if(!$account || !$account_cbu){
            Log::channel('mfo_account_errors')->error('AccountBalanceHistoryService->createHistory: Account where number = '.$account_number.' not found');
            throw new \Exception('Account where number = '.$account_number.' not found');
        }
        if(isset($operation_date) && date('Y-m-d H:i:s', strtotime($operation_date)) != $operation_date){
            throw new \Exception('Invalid operation date provided - '.$operation_date);
        }
        $operation_date = AccountingEntryService::isValidDate($operation_date) ? $operation_date : now();
        AccountBalanceHistory::query()->create([
            'account_id' => $account->id,
            'operation_date' => $operation_date,
            'balance' => $account->balance,
        ]);
        AccountBalanceHistoryCBU::query()->create([
            'account_id' => $account_cbu->id,
            'operation_date' => $operation_date,
            'balance' => $account_cbu->balance,
        ]);
    }

    /**
     * @param int $contract_id
     * @param string|null $operation_date
     * @return void
     * @throws \Exception
     */
    private function createHistoryByContractId(int $contract_id, string $operation_date = null) : void
    {
        $contract = Contract::query()->find($contract_id);
        if(!$contract){
            Log::channel('mfo_account_errors')->error('AccountBalanceHistoryService->createHistoryByContractId: Contract where ID = '.$contract_id.' not found');
            throw new \Exception('Contract where ID = '.$contract_id.' not found');
        }
        $operation_date = isset($operation_date) && date('Y-m-d H:i:s', strtotime($operation_date)) == $operation_date ? $operation_date : now();
        $accounts = Account::query()->where('contract_id',$contract_id)->get();
        foreach ($accounts as $account) {
            $this->createHistory($account->number,$operation_date);
        }
    }
}
