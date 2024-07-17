<?php

namespace App\Services\MFO;

use App\Jobs\MFO\CalculateAccountBalances1C;
use App\Models\AccountBalanceHistory1C;
use App\Models\MFOAccount;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpFoundation\Response;

final class Account1CService
{
    public function calculateBalance(Carbon $start): void
    {
        $numberOfDaysToProcess = $this->getNumberOfDaysToProcess($start);
        $start->setTime(23, 59, 59);

        $mfoAccounts = MFOAccount::whereIn('id', function (Builder $builder) {
            $builder->select('mfo_account_id')
                ->from('account_balance_histories_1c')
                ->groupBy('mfo_account_id');
        })
            ->get();

        for ($i = 0; $i < $numberOfDaysToProcess; $i++) {
            Log::channel('mfo_account')
                ->info('Calculating mfo accounts balance for ' . $start->format('Y-m-d'));

            $responseFrom1C = collect($this->getAccountsBalanceFrom1C($start, $start));

            if (empty($responseFrom1C)) {
                Log::channel('mko_to_bank_errors')
                    ->warning('No accounts data for ' . $start->format('Y-m-d'));
                continue;
            }

            $cards9430 = $responseFrom1C->where('Счет', '=', '9430')->first()['Карточки'] ?? [];
            $cards9420 = $responseFrom1C->where('Счет', '=', '9420')->first()['Карточки'] ?? [];

            foreach ($mfoAccounts as $mfoAccount) {
                $this->calculateBalanceForSpecificAccount($mfoAccount, $start, $cards9430, $cards9420);
            }
            $start->addDay();
        }
    }

    public function calculateBalanceForSpecificAccount(MFOAccount $mfoAccount, Carbon $startDate, array $cards9430, array $cards9420): void
    {
        $account1C = $mfoAccount->accounts1C()->first();
        $latestBalance = $mfoAccount->balanceHistories()
            ->where('operation_date', '<', $startDate->startOfDay())
            ->latest('operation_date')
            ->first();

        if (!$latestBalance) {
            Log::channel('mko_to_bank_errors')
                ->warning([
                    'message' => 'No latest balance found for account',
                    'number' => $account1C->number,
                    'subconto_number' => $account1C->subconto_number,
                    'date' => $startDate->format('Y-m-d'),
                ]);

            return;
        }

        if ($account1C->number === '9430') {
            $card = $this->findSpecificCard($account1C->subconto_number, $cards9430);
        } else {
            $card = $this->findSpecificCard($account1C->subconto_number, $cards9420);
        }

        if (!$card) {
            Log::channel('mko_to_bank_errors')
                ->warning([
                    'message' => 'No card found for account',
                    'number' => $account1C->number,
                    'subconto_number' => $account1C->subconto_number,
                    'date' => $startDate->format('Y-m-d'),
                ]);

            $calculatedBalance = $latestBalance->balance;
        } else {
            $calculatedBalance = ($latestBalance->balance * 100 - $card['СуммаОборотДт'] + $card['СуммаОборотКт']) / 100;
        }

        $oldBalance = $mfoAccount->balanceHistories()
            ->where('operation_date', '>=', $startDate->startOfDay()->format('Y-m-d H:i:s'))
            ->where('operation_date', '<=', $startDate->endOfDay()->format('Y-m-d H:i:s'))
            ->first();


        // if balance was already calculated, then we will update it instead of creating a new one
        if ($oldBalance) {
            $oldBalance->balance = $calculatedBalance;
            $oldBalance->save();
        } else {
            $newBalance = new AccountBalanceHistory1C();
            $newBalance->mfo_account_id = $mfoAccount->id;
            $newBalance->operation_date = $startDate;
            $newBalance->balance = $calculatedBalance;
            $newBalance->save();
        }
    }

    public function generateReport(Carbon $startDate, Carbon $endDate = null): bool
    {
        $endDate ??= now();
        $insertableAccounts = [];

        // take mfo accounts that have at least one account1C
        $mfoAccounts = MFOAccount::has('accounts1C')
            ->with('accounts1C')
            ->get();

        $responseFrom1C = $this->getAccountsBalanceFrom1C($startDate, $endDate);

        foreach ($mfoAccounts as $mfoAccount) {
            $debitAccountSum = 0;
            $creditAccountSum = 0;
            $balanceHistoryFrom = 0;
            $balanceHistoryTo = 0;
            $mark = null;
            $contractId = null;

            foreach ($mfoAccount->accounts1C as $account1C) {
                $account = $this->findSpecificAccount($account1C->number, $responseFrom1C);

                if (!$account1C->is_subconto) {
                    // not subconto account
                    if ($account) {
                        $debitAccountSum += $account['СуммаОборотДт'];
                        $creditAccountSum += $account['СуммаОборотКт'];
                        $balanceHistoryFrom += $account['СальдоНаНачало'] > 0 ? -$account['СальдоНаНачало'] : abs($account['СальдоНаНачало']);
                        $balanceHistoryTo += $account['СальдоНаКонец'] > 0 ? -$account['СальдоНаКонец'] : abs($account['СальдоНаКонец']);
                    }
                } else {
                    // subconto account
                    $card = $this->findSpecificCard($account1C->subconto_number, $account['Карточки'] ?? []);
                    if (!$account1C->is_subconto_without_remainder) {

                        // subconto account with remainder
                        if ($card) {
                            $debitAccountSum += $card['СуммаОборотДт'];
                            $creditAccountSum += $card['СуммаОборотКт'];
                            $balanceHistoryFrom += $card['СальдоНаНачало'] > 0 ? -$card['СальдоНаНачало'] : abs($card['СальдоНаНачало']);
                            $balanceHistoryTo += $card['СальдоНаКонец'] > 0 ? -$card['СальдоНаКонец'] : abs($card['СальдоНаКонец']);
                        }
                    } else {
                        // subconto account without remainder
                        if ($card) {
                            $debitAccountSum += $card['СуммаОборотДт'];
                            $creditAccountSum += $card['СуммаОборотКт'];
                        }

                        // for subconto accounts without remainder we need to get balance history from our database
                        $balanceHistoryFrom = $this->getAccountBalanceHistoryForSpecificDay(
                            $mfoAccount->id,
                            $startDate->copy()->subDay()
                        );
                        $balanceHistoryTo = $this->getAccountBalanceHistoryForSpecificDay(
                            $mfoAccount->id,
                            $endDate
                        );
                    }
                }
                    $mark ??= $account1C->type;
                    $contractId ??= $account1C->system_number;
            }
            $insertableAccounts[] = [
                'debit_account_sum' => $debitAccountSum,
                'credit_account_sum' => $creditAccountSum,
                'balance_history_from' => $balanceHistoryFrom,
                'balance_history_to' => $balanceHistoryTo,
                'number' => $mfoAccount->number,
                'mark' => $mark ?? '',
                'contract_id' => $contractId ?? null,
            ];
        }
        DB::beginTransaction();
        try {
            DB::table('accounts_1c_temp')
                ->insert($insertableAccounts);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::channel('mko_to_bank_errors')
                ->error([
                    'title' => 'Error while trying generate report',
                    'message' => $e->getMessage(),
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ]);

            return false;
        }
        DB::commit();

        return true;
    }

    public function getAccountsBalanceFrom1C(Carbon $startDate, Carbon $endDate): array
    {
        $response = Http::withBasicAuth(
            config('test.ODIN_C_LOGIN'),
            config('test.ODIN_C_PASSWORD')
        )
            ->get(config('test.ODIN_C_HOST') . '/balance/' . $startDate->format('Ymd') . '/' . $endDate->format('Ymd'));

        if ($response->status() !== Response::HTTP_OK) {
            Log::channel('mko_to_bank_errors')
                ->error('1C host returned an error while trying to get accounts balance for ' . $startDate . '. Response: ' . $response->body());

            return [];
        }

        return $response->json()['Баланс'] ?? [];
    }

    public function getNumberOfDaysToProcess(Carbon $start): int
    {
        return $start->diffInDays(now()->setTime($start->hour, $start->minute, $start->second)) + 1;
    }

    private function getNumberOfMonthsToProcess(Carbon $start, Carbon $end): int
    {
        return $start->diffInMonths($end) + 1;
    }

    private function findSpecificCard(string $subcontoNumber, array $cards)
    {
        $specificCard = null;
        foreach ($cards as $card) {
            $number = $card['3_Номер'] ?? $card['2_Номер'];
            if (trim($number) === $subcontoNumber) {
                if ($specificCard) {
                    $specificCard['СальдоНаНачало'] += $card['СальдоНаНачало'];
                    $specificCard['СальдоНаКонец'] += $card['СальдоНаКонец'];
                    $specificCard['СуммаОборотДт'] += $card['СуммаОборотДт'];
                    $specificCard['СуммаОборотКт'] += $card['СуммаОборотКт'];
                } else {
                    $specificCard = Arr::only($card, ['СальдоНаНачало', 'СальдоНаКонец', 'СуммаОборотДт', 'СуммаОборотКт']);
                }
            }
        }

        return $specificCard;
    }

    private function findSpecificAccount(string $account, array $accounts)
    {
        foreach ($accounts as $acc) {
            if (trim($acc['Счет']) === $account) {
                return $acc;
            }
        }

        return null;
    }

    private function getAccountBalanceHistoryForSpecificDay(int $mfoAccountId, Carbon $date): int
    {
        $accountBalanceHistory = AccountBalanceHistory1C::where('mfo_account_id', '=', $mfoAccountId)
            ->whereBetween('operation_date', [
                $date->startOfDay()->format('Y-m-d H:i:s'),
                $date->endOfDay()->format('Y-m-d H:i:s')
            ])
            ->first();

        if (!$accountBalanceHistory) {
            Log::channel('mko_to_bank_errors')
                ->warning([
                    'message' => 'No balance history found for account when generating report',
                    'mfo_account_id' => $mfoAccountId,
                    'date' => $date->format('Y-m-d'),
                ]);

            return 0;
        }

        return $accountBalanceHistory->balance * 100;
    }

    public function isCalculatingBalancesAlreadyInProgress(): bool
    {
        $queuedJobs = Queue::getRedis()
            ->connection()
            ->zrange('queues:default:reserved', 0, -1);

        foreach ($queuedJobs as $job) {
            if (json_decode($job, true)['displayName'] = CalculateAccountBalances1C::class) {
                return true;
            }
        }

        return false;
    }
}

