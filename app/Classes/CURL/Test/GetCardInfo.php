<?php

namespace App\Classes\CURL\test;

use Illuminate\Support\Facades\Log;

class GetCardInfo extends Basetest
{
    public function getCardInfo(string $number, string $expiryDate)
    {
        $id = 'test_' . uniqid(rand(), 1);
        $this->url = $this->route() . 'v1/rpc/cards';
        $this->makeRequest('card.register');
        $this->addParamToRequest('id', $id);
        $this->addParamsByKey(['number' => $number, 'expiryDate' => $expiryDate]);
        Log::channel('payment')->info(print_r($this, 1));
        $this->execute();
        $result = $this->response();
        Log::channel('payment')->info(print_r($result, 1));
        return $result;
    }
}
