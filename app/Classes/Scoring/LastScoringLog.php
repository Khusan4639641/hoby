<?php

namespace App\Classes\Scoring;

use App\Models\CardScoringLog;
use Carbon\Carbon;

class LastScoringLog
{
    private $buyer_id;
    private $card_num;


    public function __construct(int $buyer_id, string $card_num)
    {
        $this->buyer_id = $buyer_id;
        $this->card_num = str_replace(' ', '', $card_num);
    }

    public function getLastScoring(){
        //get all scoring logs by buyer & card number
        $scoring_logs = CardScoringLog::where('user_id', $this->buyer_id)
            ->where('card_hash', md5($this->card_num))
            ->where('status', 1)
            ->get();

        if(empty($scoring_logs)){
            return false;
        }

        //get last scoring log by date
        $last_scoring_log = $scoring_logs->sortBy('created_at')->last();

        return $last_scoring_log;
    }

    public function getMonthDifference()
    {
        $last_scoring_log = $this->getLastScoring();

        if ($last_scoring_log == null) {
            return 1000;
        }

        $difference_month = Carbon::parse($last_scoring_log['created_at'])->diffInMonths(Carbon::now());

        return $difference_month;
    }
}
