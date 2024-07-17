<?php

namespace App\Services\KATM;

use App\Classes\Exceptions\KatmReportException;
use App\Models\AccountingEntry;
use App\Models\AccountingPaymentPurpose;
use App\Models\AccountParameter;
use App\Models\Contract;
use App\Models\MfoSettings;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CollectDataToKatmService
{

    private ?MfoSettings $settings = null;

    private $accountsTypes = [
        '12401',
        '12405',
        '12409',
        '12499',
        '16307',
        '15701',
        '19997',
    ];

    public function getAccountsTypes(): array
    {
        return $this->accountsTypes;
    }

    public function getSettings(): MfoSettings
    {
        if (!$this->settings) {
            $settings = MfoSettings::query()->first();
            if (!empty($settings)) {
                $this->settings = $settings->getModel();
            } else {
                throw new \RuntimeException("Не найдены настройки mfo_settings");
            }
        }
        return $this->settings;
    }

    public function getAccountBalance(Contract $contract, $accountMask): float
    {
        $contractID = $contract->id;
        return $contract
            ->accountingEntries()
            ->where(function ($query) use ($accountMask, $contractID) {
                $query->whereHas('debitAccount', function ($query) use ($accountMask, $contractID) {
                    $query->where('mask', $accountMask)
                        ->where('contract_id', $contractID);
                })
                    ->orWhereHas('creditAccount', function ($query) use ($accountMask, $contractID) {
                        $query->where('mask', $accountMask)
                            ->where('contract_id', $contractID);
                    });
            })
            ->selectRaw("IFNULL(SUM(IF (SUBSTRING(accounting_entries.debit_account, 1, 5) = $accountMask, accounting_entries.amount, -accounting_entries.amount)), 0) AS sum")
            ->first()->sum;
    }

    public function getAccountBalanceToDate(Contract $contract, $accountMask, $date, bool $isBeg = true): float
    {
        $date = Carbon::parse($date)->format('Y-m-d');
        $contractID = $contract->id;
        $accountBalanceQuery = $contract
            ->accountingEntries()
            ->where(function ($query) use ($accountMask, $contractID) {
                $query->whereHas('debitAccount', function ($query) use ($accountMask, $contractID) {
                    $query->where('mask', $accountMask)
                        ->where('contract_id', $contractID);
                })
                    ->orWhereHas('creditAccount', function ($query) use ($accountMask, $contractID) {
                        $query->where('mask', $accountMask)
                            ->where('contract_id', $contractID);
                    });
            })
            ->selectRaw("IFNULL(SUM(IF (SUBSTRING(accounting_entries.debit_account, 1, 5) = $accountMask, accounting_entries.amount, -accounting_entries.amount)), 0) AS sum");
        if ($isBeg) {
            $accountBalanceQuery->whereRaw("DATE(accounting_entries.operation_date) < '$date'");
        } else {
            $accountBalanceQuery->whereRaw("DATE(accounting_entries.operation_date) <= '$date'");
        }
        return $accountBalanceQuery->first()->sum;
    }

    public function collectAccountsBalances(Contract $contract, $date, bool $allAccounts = false): array
    {
        $date = Carbon::parse($date)->format('Y-m-d');

        $resultArr = [];

        $accountsTypes = $this->accountsTypes;

        $accounts = $contract->accounts()
            ->whereIn('mask', $accountsTypes)
            ->get();

        foreach ($accounts as $account) {

            $balanceHistoryBegNumber = $this->getAccountBalanceToDate($contract, $account['mask'], $date);
            $balanceHistoryEndNumber = $this->getAccountBalanceToDate($contract, $account['mask'], $date, false);

            $debit = $contract->accountingEntries()
                ->where('debit_account', $account['number'])
                ->whereRaw("DATE(operation_date) = '$date'")
                ->sum('amount');

            $credit = $contract->accountingEntries()
                ->where('credit_account', $account['number'])
                ->whereRaw("DATE(operation_date) = '$date'")
                ->sum('amount');

            $operationDate = $contract->accountingEntries()
                ->where(function ($query) use ($account) {
                    $query->where('debit_account', $account['number'])
                        ->orWhere('credit_account', $account['number']);
                })
                ->whereRaw("DATE(operation_date) = '$date'")
                ->selectRaw('MAX(operation_date) AS date')
                ->first();

            if (!$allAccounts && $debit === 0 && $credit === 0) {
                continue;
            }

            $resultArr[] = [
                'account' => $account['number'],
                'date' => Carbon::parse($operationDate->date)->format('Y-m-d H:i:s'),
                'startBalance' => $balanceHistoryBegNumber,
                'debit' => $debit,
                'credit' => $credit,
                'endBalance' => $balanceHistoryEndNumber,
            ];

        }

        return $resultArr;
    }

    /**
     * @throws KatmReportException
     */
    public function collectPayments(Contract $contract, $date): array
    {
        $date = Carbon::parse($date)->format('Y-m-d');
        $resultArr = [];

        $entries = $contract->accountingEntries()
            ->where('status', AccountingEntry::STATUS_ACTIVE)
            ->whereIn('destination_code', [
                AccountingEntry::CODE_1007,
                AccountingEntry::CODE_1008,
                AccountingEntry::CODE_1009
            ])
            ->whereRaw("DATE(operation_date) = '$date'")
            ->get();

        foreach ($entries as $entry) {

            $debitAccountMask = Str::substr($entry->debit_account, 0, 5);

            if (!AccountParameter::query()
                ->where('mask', $debitAccountMask)
                ->where('balance_type', 1)
                ->exists()) {
                continue;
            }

            $creditAccountMask = Str::substr($entry->credit_account, 0, 5);

            if (!AccountParameter::query()
                ->where('mask', $creditAccountMask)
                ->where('balance_type', 1)
                ->exists()) {
                continue;
            }

            $accountingPaymentPurpose = AccountingPaymentPurpose::query()
                ->where('code', $entry->destination_code)
                ->first();

            if (!$accountingPaymentPurpose) {
                throw new KatmReportException('В таблице accounting_payment_purpose отсутсвует code - ' . $entry->destination_code);
            }

            $nameA = $contract->generalCompany->name_ru;
            $nameB = $contract->buyer->fio;
            if ($accountingPaymentPurpose->company_type === AccountingPaymentPurpose::TYPE_RECEIVER) {
                $nameA = $contract->buyer->fio;
                $nameB = $contract->generalCompany->name_ru;
            }

            $resultArr[] = [
                'accountA' => $entry->debit_account,
                'accountB' => $entry->credit_account,
                'branchA' => $this->getSettings()->bank_code,
                'branchB' => $this->getSettings()->bank_code,
                'coaA' => $debitAccountMask,
                'coaB' => $creditAccountMask,
                'currency' => $this->getSettings()->currency_code_uzs,
                'destination' => $entry->destination_code,
                'docDate' => $entry->operation_date,
                'docNum' => Str::substr($entry->payment_id, -10),
                'docType' => $this->getSettings()->payment_purpose_code,
                'nameA' => $nameA,
                'nameB' => $nameB,
                'payType' => $this->getSettings()->issuance_form,
                'paymentId' => $entry->payment_id,
                'purpose' => $accountingPaymentPurpose->title,
                'summa' => $entry->amount,
            ];
        }

        return $resultArr;
    }

    public function collectAccounts(Contract $contract): array
    {
        $resultArr = [];
        $accounts = $contract->accounts;
        foreach ($accounts as $account) {
            if (!in_array($account->getMask(), $this->accountsTypes, false)) {
                continue;
            }
            $resultArr[] = [
                'date' => Carbon::parse($account->updated_at)->format('Y-m-d H:i:s'),
                'account' => $account->number,
                'coa' => $account->getMask(),
                'dateOpen' => Carbon::parse($account->created_at)->format('Y-m-d H:i:s'),
                'dateClose' => $account->closed_at !== null ? Carbon::parse($account->closed_at)->format('Y-m-d H:i:s') : "",
            ];
        }
        return $resultArr;
    }

}
