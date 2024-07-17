<?php

namespace App\Classes\Universal;

class DailyPaymentsResponse
{

//    private $currentPayment = null;
    private $payments;
    private $count;

    public function __construct(array $response)
    {

        $responseResult = $response['result'];

        $this->payments = $responseResult['data'];
        $this->count = $responseResult['total_elements'];

//        if (count($this->payments) > 0) {
//            $this->currentPayment = $this->payments[0];
//        }

    }

    public function reverse()
    {
        $this->payments = array_reverse($this->payments);
    }

    public function isActual(): bool
    {
        return $this->count > 0;
    }


}
