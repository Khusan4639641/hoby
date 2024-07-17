<?php

namespace App\Classes\CURL\Universal;

use App\Classes\CURL\Universal\HUMO\CardScoringRequest as HUMOCardScoringRequest;
use App\Classes\CURL\Universal\UZCARD\CardScoringRequest as UZCARDCardScoringRequest;

class CardScoringRequest extends UniversalRequest
{

    /**
     * @param string $number
     * @param string $expire
     * @throws \Exception
     */
    public function __construct(string $number, string $expire)
    {
        parent::__construct($number);
        if ($this->cardType->isUzcard()) {
            $this->source = new UZCARDCardScoringRequest($number, $expire);
        } else if ($this->cardType->isHumo()) {
            $this->source = new HUMOCardScoringRequest($number, $expire);
        } else {
            throw new \Exception('Не удалось обратиться к сервису UNIVERSAL. Не идентифицирована карта', compact('number', 'expire'));
        }
    }

}
