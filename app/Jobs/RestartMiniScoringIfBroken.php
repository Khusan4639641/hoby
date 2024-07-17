<?php

namespace App\Jobs;

use App\Models\Buyer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\GradeScoringService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class RestartMiniScoringIfBroken implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $attempt;

    const STATE_AWAIT_RESPONSE = 3;
    const STATE_FAILED_RESPONSE = 4;
    const MAX_ATTEMPTS = 10;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $userId, int $attempt = 1)
    {
        $this->userId = $userId;
        $this->attempt = $attempt;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $buyer = Buyer::with('scoringResultMini')->find($this->userId);

        $miniScoringResult = $buyer->scoringResultMini->last();

        $attempt = $this->attempt;
        $total_state = $miniScoringResult->total_state;

        Log::channel('scoring_mini_check_status')->info('Mini Scoring check status result', [
            'User ID' => $this->userId,
            'Attempt' => $attempt,
            'Total State' => $total_state,
        ]);

        if ($miniScoringResult) {

            /* Attempts limit not reached -> proceed */
            if ($attempt <= self::MAX_ATTEMPTS) {

                if ($total_state == self::STATE_AWAIT_RESPONSE) {

                    $this->incrementAttemptAndRepeatJob();

                } elseif ($total_state == self::STATE_FAILED_RESPONSE) {

                    $gradeScoringService = new GradeScoringService();
                    $gradeScoringService->initMiniScoring($this->userId);

                    $this->incrementAttemptAndRepeatJob();
                }

            } else {

                if (in_array($total_state, [self::STATE_AWAIT_RESPONSE, self::STATE_FAILED_RESPONSE])) {

                    $miniScoringResult->attempts_limit_reached = 1;
                    $miniScoringResult->save();
                }
            }
        }
    }

    private function incrementAttemptAndRepeatJob()
    {
        RestartMiniScoringIfBroken::dispatch($this->userId, $this->attempt+1)
            ->delay(now()->addMinutes(Config::get('test.mini_scoring_check_status_period')));
    }
}
