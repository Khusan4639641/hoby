<?php

namespace App\Classes\Payments;

use App\Classes\Payments\Interfaces\ITransaction;

class Payment
{

    private $transaction;

    public function __construct(ITransaction $payment,
                                int         $userID,
                                float       $amount,
                                string      $paymentType,
                                string      $paymentSystem,
                                int         $status,
                                int         $contractID = null,
                                int         $orderID = null,
                                int         $scheduleID = null,
                                int         $cardID = null,
                                string      $transactionID = null,
                                string      $uuid = null,
                                int         $state = null,
                                int         $reason = null
    )
    {
        $payment->createTransaction(
            $userID,
            $amount,
            $paymentType,
            $paymentSystem,
            $status,
            $contractID,
            $orderID,
            $scheduleID,
            $cardID,
            $transactionID,
            $uuid,
            $state,
            $reason
        );
        $this->transaction = $payment;
    }

    public function execute()
    {
        $this->transaction->executeTransaction();
    }

    public function id(): int
    {
        return $this->transaction->id;
    }

}
