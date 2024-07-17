<?php

namespace App\Classes\Universal\Autopayment;

class BindDebit extends BaseAutopayment
{

    public function __construct()
    {
        parent::__construct();
        $this->makeRequest('update.debits');
    }

    public function bind($clientID, $debitID, $amount)
    {
        $this->addParam([
            'client_id' => $clientID,
            'debit_id' => $debitID,
            'amount' => $amount,
        ]);
        return $this;
    }

}
