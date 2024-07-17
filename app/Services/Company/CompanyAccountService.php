<?php

namespace App\Services\Company;

use App\Models\CompanyAccount;

class CompanyAccountService
{
    public function create(
        int    $company_id,
        string $name,
        string $payment_account,
        string $mfo
    ): array
    {
        $companyAccount = new CompanyAccount();
        $companyAccount->company_id = $company_id;
        $companyAccount->name = $name;
        $companyAccount->payment_account = $payment_account;
        $companyAccount->mfo = $mfo;
        $companyAccount->save();
        return $companyAccount->toArray();
    }

    public function update(
        int    $model_id,
        string $name,
        string $payment_account,
        string $mfo
    ): array
    {
        $companyAccount = CompanyAccount::query()->find($model_id);
        $companyAccount->name = $name;
        $companyAccount->payment_account = $payment_account;
        $companyAccount->mfo = $mfo;
        $companyAccount->update();
        return $companyAccount->toArray();
    }

    public function delete(int $model_id): array
    {
        $companyAccount = CompanyAccount::query()->find($model_id);
        if ($companyAccount) {
            $companyAccount->delete();
        } else {
            return [];
        }
        return $companyAccount->toArray();
    }
}
