<?php

namespace App\Services\API\V3;

use App\Jobs\RestartMiniScoringIfBroken;
use App\Models\ScoringResult;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Services\GradeScoringService;
use Illuminate\Support\Facades\Config;

class MiniScoringService extends BaseService
{
    public function init() {

        $buyer = Auth::user();

        if (!$buyer) {
            self::handleError([__('api.buyer_not_found')]);
        }

        if ($buyer->status === User::KYC_STATUS_VERIFY) {
            self::handleError([__('api.user_verified')]);
        }

        if ( !($buyer->settings && $buyer->settings->mini_limit > 0) ) {

            $miniScoringResult = $buyer->scoringResultMini->last();
            if ($miniScoringResult && $miniScoringResult->attempts_limit_reached == 1) {
                $miniScoringResult->attempts_limit_reached = 0;
                $miniScoringResult->save();
            }

            $gradeScoringService = new GradeScoringService();

            $gradeScoringService->initMiniScoring($buyer->id);

            RestartMiniScoringIfBroken::dispatch($buyer->id)->delay(now()->addMinutes(Config::get('test.mini_scoring_check_status_period')));
        }

        self::handleResponse();
    }

    public function checkStatus() {

        $user = Auth::user();

        if (!$user) {
            self::handleError([__('api.buyer_not_found')]);
        }

        $gradeScoringService = new GradeScoringService();

        $state = $gradeScoringService->miniScoringStateReport($user->id);

        self::handleResponse(['status' => $state['total_state']]);
    }
}
