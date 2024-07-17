<?php

namespace App\Classes\Payments;

use App\Classes\Payments\Interfaces\ITransaction;

class AccountPayment extends BasePayment
{

    public function __construct(ITransaction $payment,
                                int          $userID,
                                float        $amount,
                                int          $contractID,
                                int          $orderID,
                                int          $scheduleID,
                                int          $cardID = null,
                                int          $status = 1
    )
    {
        parent::__construct($payment,
            $userID,
            $amount,
            $status,
            $contractID,
            $orderID,
            $scheduleID,
            $cardID
        );
        $paymentType = $this->paymentType();
        $paymentSystem = $this->paymentSystem();
        $this->logInfo("Оплата с личного счёта. Платёжка создана",
            [
                'userID' => $userID,
                'amount' => $amount,
                'paymentType' => $paymentType,
                'paymentSystem' => $paymentSystem,
                'contractID' => $contractID,
                'orderID' => $orderID,
                'scheduleID' => $scheduleID,
            ]);
    }

    public function paymentType(): string
    {
        return 'auto';
    }

    public function paymentSystem(): string
    {
        return 'ACCOUNT';
    }
}
