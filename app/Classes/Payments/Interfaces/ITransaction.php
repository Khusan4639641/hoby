<?php

namespace App\Classes\Payments\Interfaces;

interface ITransaction
{

    public function createTransaction(int    $userID,
                             float  $amount,
                             string $paymentType,
                             string $paymentSystem,
                             int    $status,
                             int    $contractID = null,
                             int    $orderID = null,
                             int    $scheduleID = null,
                             int    $cardID = null,
                             string $transactionID = null,
                             string $uuid = null,
                             int    $state = null,
                             int    $reason = null
    );

    public function executeTransaction();

}
