<?php

namespace App\Classes\CURL\Universal\UZCARD;

use App\Classes\ApiResponses\Universal\CardScoringResponse;
use Carbon\Carbon;

class CardScoringRequest extends BaseUzcardRequest
{
    public function __construct(string $number, string $expire)
    {
        parent::__construct();
        $month = config('test.scoring_max_month');
        $this->makeRequest('card.scoring');
        $this->addParamByKey('card_number', $number);
        $this->addParamByKey('expire', $expire);
        $this->addParamByKey('start_date', Carbon::now()->subMonth($month)->format('Ym01'));
        $this->addParamByKey('end_date', Carbon::now()->format('Ym25'));
    }

    public function response(): CardScoringResponse
    {
        return new CardScoringResponse($this->responseBody->json());
    }

}


