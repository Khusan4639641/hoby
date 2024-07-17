<?php

namespace App\Classes\Payments;

use App\Classes\Payments\Interfaces\ITransaction;

class HumoToAccountPayment extends BasePayment
{

    public function __construct(ITransaction $payment,
                                int          $userID,
                                float        $amount,
                                int          $cardID,
                                string       $transactionID,
                                string       $uuid)
    {
        parent::__construct($payment,
            $userID,
            $amount,
            1,
            null,
            null,
            null,
            $cardID,
            $transactionID,
            $uuid);
        $paymentType = $this->paymentType();
        $paymentSystem = $this->paymentSystem();
        $this->logInfo("Приход с карты HUMO на личный счёт. Платёжка создана",
            [
                'userID' => $userID,
                'amount' => $amount,
                'paymentType' => $paymentType,
                'paymentSystem' => $paymentSystem,
                'cardID' => $cardID,
                'transactionID' => $transactionID,
                'uuid' => $uuid
            ]);
    }

    public function paymentType(): string
    {
        return 'user';
    }

    public function paymentSystem(): string
    {
        return 'HUMO';
    }
}
