<?php

namespace App\Classes\CURL\test;

use Illuminate\Support\Facades\Log;

class RefundPayment extends Basetest
{
    public function refundPayment(string $transactionID)
    {
        $id = 'test_' . uniqid(rand(), 1);
        $this->url = $this->route() . 'v1/rpc/payments/revert';
        $this->makeRequest('payment.cancel');
        $this->addParamByKey('payment_id', $transactionID);
        $this->addParamToRequest('id', $id);

        Log::channel('payment')->info(print_r($this->requestBody, 1));
        Log::channel('payment')->info(print_r($this->url, 1));

        $this->execute();
        $result = $this->response();
        if($result['status'] == 'success')
            $result['request'] = $this->requestBody;

        Log::channel('payment')->info(print_r($result, 1));

        return $result;
    }
}
