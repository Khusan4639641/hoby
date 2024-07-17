<?php

namespace App\Classes\Payments;

use App\Classes\Payments\Interfaces\ITransaction;

class AutopayPayment extends BasePayment
{

    public function __construct(ITransaction $payment, int $userID, float $amount)
    {
        parent::__construct($payment, $userID, $amount, 1);
        $paymentType = $this->paymentType();
        $paymentSystem = $this->paymentSystem();
        $this->logInfo("Приход с Autopay на личный счёт. Платёжка создана",
            [
                'userID' => $userID,
                'amount' => $amount,
                'paymentType' => $paymentType,
                'paymentSystem' => $paymentSystem,
            ]);
    }

    public function paymentType(): string
    {
        return 'user';
    }

    public function paymentSystem(): string
    {
        return 'Autopay';
    }
}
