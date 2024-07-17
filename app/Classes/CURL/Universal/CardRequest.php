<?php

namespace App\Classes\CURL\Universal;

use App\Classes\CURL\Universal\UZCARD\CardRequest as UZCARDCardRequest;
use App\Classes\CURL\Universal\HUMO\CardRequest as HUMOCardRequest;

class CardRequest extends UniversalRequest
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
            $this->source = new UZCARDCardRequest($number, $expire);
        } else if ($this->cardType->isHumo()) {
            $this->source = new HUMOCardRequest($number, $expire);
        } else {
            throw new \Exception('Не удалось обратиться к сервису UNIVERSAL. Не идентифицирована карта', compact('number', 'expire'));
        }
    }

}
