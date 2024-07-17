<?php

namespace App\Classes\CURL\test;

use App\Classes\CURL\Interfaces\ICard;
use App\Classes\Exceptions\testException;
use Illuminate\Support\Facades\Config;

class PaymentsRevertRequest extends BasetestRequest
{

    public function __construct(string $paymentID)
    {
        parent::__construct();
        $this->makeRequest('payment.cancel');
        $this->addParamByKey('payment_id', $paymentID);
    }

    protected function url(): string
    {
        return $this->baseUrl . '/v1/rpc/payments/revert';
    }

}
