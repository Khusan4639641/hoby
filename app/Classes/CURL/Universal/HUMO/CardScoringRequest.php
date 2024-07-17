<?php

namespace App\Classes\CURL\Universal\HUMO;

use App\Classes\ApiResponses\Universal\CardScoringResponse;

class CardScoringRequest extends BaseHumoRequest
{
    public function __construct(string $number, string $expire)
    {
        parent::__construct();
        $this->makeRequest('humo.scoring');
        $this->addParamByKey('card_number', $number);
        $this->addParamByKey('expire', $expire);
    }

    public function response(): CardScoringResponse
    {
        return new CardScoringResponse($this->responseBody->json());
    }

}

