<?php

namespace App\Services;

use App\Models\Buyer;

class BuyerLimitService
{

    public int $min_border_of_mini_limit;
    public int $max_border_of_mini_limit;
    public int $percentage;
    public function __construct()
    {
        $this->min_border_of_mini_limit = config('test.scoring.limit.min'); //SCORING_LIMIT_MIN
        $this->max_border_of_mini_limit = config('test.scoring.limit.max'); //SCORING_LIMIT_MAX // = 2000000
        $this->percentage = config('test.scoring.limit.percentage'); //LIMIT_PERCENTAGE_CALCULATE
    }

    private function cutMiniLimit(int $limit): int
    {
        $limit = $limit > $this->min_border_of_mini_limit ? $limit : $this->min_border_of_mini_limit;
        return $limit < $this->max_border_of_mini_limit ? $limit : $this->max_border_of_mini_limit;
    }

    public function setLimit(Buyer $buyer, int $limit)
    {
        $buyerSetting = $buyer->settings;

        if($limit > 5000000){
            $miniLimit = self::cutMiniLimit((ceil($limit / 1000000) * 1000000) / $this->percentage);
        }else{
            $miniLimit = $this->min_border_of_mini_limit;
        }

        //$miniLimit = self::cutMiniLimit($limit > 5000000 ? ((round(number_format($limit, 2, ',', '.')) * 1000000) * (int)$this->percentage) / 100 : $this->min_border_of_mini_limit);

        $diff = $buyerSetting->mini_limit - $buyerSetting->mini_balance;
        $buyerSetting->mini_limit = $miniLimit;

        $buyerSetting->mini_balance = $miniLimit - $diff;

        $buyerSetting->limit = $limit;
        $buyerSetting->balance = $limit;
        $buyerSetting->save();
    }

    public function setMiniLimit(Buyer $buyer): void
    {
        $limit = $this->min_border_of_mini_limit;
        $buyerSetting = $buyer->settings;
        if (!($buyerSetting->mini_limit > 0)) {
            $limit = $this->cutMiniLimit($limit);
            $buyerSetting->mini_limit = $limit;
            $buyerSetting->mini_balance = $limit;
            $buyerSetting->save();
        }
    }

}
