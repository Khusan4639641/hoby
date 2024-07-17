<?php

namespace App\Services\API\V3\Account;

use App\DTO\V3\Account\Account1cDTO;
use App\DTO\V3\Account\MFOAccountDTO;
use App\Models\Account1C;
use App\Models\Account1CMFOAccount;
use App\Models\MFOAccount;
use \App\Services\API\V3\BaseService;

class AccountService extends BaseService
{
    public static function index()
    {
        return Account1CMFOAccount::join('accounts_1c', 'accounts_1c.id', 'account_1c_mfo_account.account_1c_id')
            ->join('mfo_accounts', 'mfo_accounts.id', 'account_1c_mfo_account.mfo_account_id')
            ->orderBy('account_1c_mfo_account.id')
            ->paginate(10, ['account_1c_mfo_account.id', 'accounts_1c.number as account_1c_number', 'mfo_accounts.number as mfo_account_number', 'name as account_1c_name', 'is_subconto', 'subconto_number', 'accounts_1c.system_number as account_system_number', 'accounts_1c.type as account_type', 'accounts_1c.is_subconto_without_remainder']);
    }

    public static function update(Account1CMFOAccount $account_1c_mfo_account, MFOAccountDTO $mfo_account_dto, Account1cDTO $account_1c_dto): void
    {
        if (!$MFOAccount = MFOAccount::where('number', $mfo_account_dto->number)->first()) {
            $MFOAccount = new MFOAccount();
            $MFOAccount->number = $mfo_account_dto->number;
            $MFOAccount->save();
        }

        if (!$account1C = Account1C::where(['number' => $account_1c_dto->number, 'subconto_number' => $account_1c_dto->subconto_number])->first()) {
            $account1C = new Account1C();
            $account1C->number = $account_1c_dto->number;
            $account1C->subconto_number = $account_1c_dto->subconto_number;
        }
        $account1C->name = $account_1c_dto->name;
        $account1C->is_subconto = $account_1c_dto->is_subconto;
        $account1C->type = $account_1c_dto->type;
        $account1C->system_number = $account_1c_dto->system_number;
        $account1C->is_subconto_without_remainder = $account_1c_dto->is_subconto_without_remainder;
        $account1C->save();

        if (!Account1CMFOAccount::where(['mfo_account_id' => $MFOAccount->id, 'account_1c_id' => $account1C->id])->exists()) {
            $account_1c_mfo_account->mfo_account_id = $MFOAccount->id;
            $account_1c_mfo_account->account_1c_id = $account1C->id;
            $account_1c_mfo_account->save();
        }
        BaseService::handleResponse(['successfully_updated']);
    }

    public static function store(MFOAccountDTO $mfo_account_dto, Account1cDTO $account_1c_dto): void
    {
        if (!$mfoAccount = MFOAccount::where('number', $mfo_account_dto->number)->first()) {
            $mfoAccount = new MFOAccount();
            $mfoAccount->number = $mfo_account_dto->number;
            $mfoAccount->save();
        }
        if (!$account1C = Account1C::where(['number' => $account_1c_dto->number, 'subconto_number' => $account_1c_dto->subconto_number])->first()) {
            $account1C = new Account1C();
            $account1C->number = $account_1c_dto->number;
            $account1C->subconto_number = $account_1c_dto->subconto_number;
        }
        $account1C->name = $account_1c_dto->name;
        $account1C->is_subconto = $account_1c_dto->is_subconto;
        $account1C->type = $account_1c_dto->type;
        $account1C->system_number = $account_1c_dto->system_number;
        $account1C->is_subconto_without_remainder = $account_1c_dto->is_subconto_without_remainder;
        $account1C->save();

        if (!Account1CMFOAccount::where(['account_1c_id' => $account1C->id, 'mfo_account_id' => $mfoAccount->id])->exists()) {
            $account1CMFOAccount = new Account1CMFOAccount();
            $account1CMFOAccount->mfo_account_id = $mfoAccount->id;
            $account1CMFOAccount->account_1c_id = $account1C->id;
            $account1CMFOAccount->save();
        }
        BaseService::handleResponse(['successfully_created']);
    }

    public static function destroy(Account1CMFOAccount $account_1c_mfo_account)
    {
        $account_1c_mfo_account->delete();
        BaseService::handleResponse(['successfully_deleted']);
    }
}
