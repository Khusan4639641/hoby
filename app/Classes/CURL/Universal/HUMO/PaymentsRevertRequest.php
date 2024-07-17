<?php

namespace App\Classes\CURL\Universal\HUMO;

class PaymentsRevertRequest extends BaseHumoRequest
{

    public function __construct(string $paymentID, string $uuid)
    {
        parent::__construct();
        $this->makeRequest('payment.cancel');
        $this->addParamByKey('payment_id', $paymentID);
        $this->addParamByKey('uuid', $uuid);
    }

}
