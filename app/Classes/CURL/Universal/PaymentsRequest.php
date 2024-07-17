<?php

namespace App\Classes\CURL\Universal;

use App\Classes\CURL\Universal\HUMO\PaymentsRequest as HUMOPaymentsRequest;
use App\Classes\CURL\Universal\UZCARD\PaymentsRequest as UZCARDPaymentsRequest;

class PaymentsRequest extends UniversalRequest
{

    /**
     * @param string $number
     * @param string $expire
     * @throws \Exception
     */
    public function __construct(string $number, string $expire, float $amount)
    {
        parent::__construct($number);
        if ($this->cardType->isUzcard()) {
            $this->source = new UZCARDPaymentsRequest($number, $expire, $amount);
        } else if ($this->cardType->isHumo()) {
            $this->source = new HUMOPaymentsRequest($number, $expire, $amount);
        } else {
            throw new \Exception('Не удалось обратиться к сервису UNIVERSAL. Не идентифицирована карта', compact('number', 'expire'));
        }
    }

}
