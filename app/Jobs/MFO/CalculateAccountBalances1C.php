<?php

namespace App\Jobs\MFO;

use App\Services\MFO\Account1CService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CalculateAccountBalances1C implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 180;
    private string $processId;
    private Carbon $startDate;
    private Collection $mfoAccounts;

    public const IN_PROGRESS = 'in_progress';
    public const FINISHED = 'finished';
    public const PREFIX = 'calculate_account_balances_1c_';
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $processId, Carbon $startDate, Collection $mfoAccounts)
    {
        $this->processId = $processId;
        $this->startDate = $startDate;
        $this->mfoAccounts = $mfoAccounts;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Account1CService $account1CService)
    {
        Cache::driver('redis')->set(self::PREFIX . $this->processId, [
            'status' => self::IN_PROGRESS,
            'percentage' => 0,
            'estimated_remaining_time' => '00:00'
        ], 60);

        $numberOfDaysToProcess = $account1CService->getNumberOfDaysToProcess($this->startDate->addDay());
        $startedAt = time();
        $loggedAt = time();

        for ($i = 0; $i < $numberOfDaysToProcess; $i++) {
            $responseFrom1C = collect($account1CService->getAccountsBalanceFrom1C($this->startDate, $this->startDate));

            foreach ($this->mfoAccounts as $mfoAccount) {
                $account1CService->calculateBalanceForSpecificAccount(
                    $mfoAccount,
                    $this->startDate,
                    $responseFrom1C->where('Счет', '=', '9430')->first()['Карточки'] ?? [],
                    $responseFrom1C->where('Счет', '=', '9420')->first()['Карточки'] ?? []
                );
            }

            $estimatedRemainingTime = gmdate('i:s', round((time() - $startedAt) / ($i + 1) * ($numberOfDaysToProcess - $i - 1), 2));
            $percentage = round(($i + 1) / $numberOfDaysToProcess * 100, 2);

            if (intval($percentage) !== 100) {
                $processStatus = [
                    'status' => self::IN_PROGRESS,
                    'percentage' => $percentage,
                    'estimated_remaining_time' => $estimatedRemainingTime
                ];

                if (time() - $loggedAt >= 10) {
                    $loggedAt = time();
                    Cache::driver('redis')->set(self::PREFIX . $this->processId, $processStatus, 60);
                }
            }

            $this->startDate->addDay();
        }

        $processStatus = [
            'status' => self::FINISHED,
            'percentage' => 100,
            'estimated_remaining_time' => '00:00'
        ];

        Cache::driver('redis')->set(self::PREFIX . $this->processId, $processStatus, 60);
    }
}
