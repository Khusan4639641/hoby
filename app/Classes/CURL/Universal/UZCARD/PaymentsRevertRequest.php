<?php

namespace App\Classes\CURL\Universal\UZCARD;

class PaymentsRevertRequest extends BaseUzcardRequest
{

    public function __construct(string $paymentID)
    {
        parent::__construct();
        $this->makeRequest('payment.cancel');
        $this->addParamByKey('payment_id', $paymentID);
    }

}
