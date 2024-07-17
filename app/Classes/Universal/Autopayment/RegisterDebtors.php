<?php

namespace App\Classes\Universal\Autopayment;

class RegisterDebtors extends BaseAutopayment
{

    public function __construct()
    {
        parent::__construct();
        $this->makeRequest('client.register');
    }

    public function addDebtor($passportID, $clientID, $fullName)
    {
        $this->addParam([
            'passport_id' => $passportID,
            'client_id' => $clientID,
            'full_name' => $fullName,
        ]);
        return $this;
    }

}
