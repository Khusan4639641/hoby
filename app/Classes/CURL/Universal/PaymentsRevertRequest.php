<?php

namespace App\Classes\CURL\Universal;

use App\Classes\CURL\Universal\HUMO\PaymentsRevertRequest as HUMOPaymentsRevertRequest;
use App\Classes\CURL\Universal\UZCARD\PaymentsRevertRequest as UZCARDPaymentsRevertRequest;

class PaymentsRevertRequest extends UniversalRequest
{

    /**
     * @throws \Exception
     */
    public function __construct(string $number, string $paymentID, string $uuid = "")
    {
        parent::__construct($number);
        if ($this->cardType->isUzcard()) {
            $this->source = new UZCARDPaymentsRevertRequest($paymentID);
        } else if ($this->cardType->isHumo()) {
            $this->source = new HUMOPaymentsRevertRequest($paymentID, $uuid);
        } else {
            throw new \Exception('Не удалось обратиться к сервису UNIVERSAL. Не идентифицирована карта', compact('number', 'paymentID', 'uuid'));
        }
    }

}
