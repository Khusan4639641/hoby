<?php

namespace App\Classes\Payments;

use App\Classes\Payments\Interfaces\ITransaction;
use App\Traits\LogTrait;

abstract class BasePayment
{

    use LogTrait;

    private $transaction;

    public function __construct(ITransaction $payment,
                                int          $userID,
                                float        $amount,
                                int          $status,
                                int          $contractID = null,
                                int          $orderID = null,
                                int          $scheduleID = null,
                                int          $cardID = null,
                                string       $transactionID = null,
                                string       $uuid = null,
                                int          $state = null,
                                int          $reason = null
    )
    {
        $this->channel = 'payments';
        $this->transaction = new Payment($payment,
            $userID,
            $amount,
            $this->paymentType(),
            $this->paymentSystem(),
            $status,
            $contractID,
            $orderID,
            $scheduleID,
            $cardID,
            $transactionID,
            $uuid,
            $state,
            $reason);
        $this->transaction->execute();
    }

    public function id(): int
    {
        return $this->transaction->id();
    }

    abstract public function paymentType(): string;

    abstract public function paymentSystem(): string;

}
