<?php

namespace App\Services\Payment;

use App\Services\resusBank\Config\resusBankConfigContract;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ApelsinPaymentService
{
    public resusBankConfigContract $resusBankConfigContract;

    public function __construct()
    {
        $this->resusBankConfigContract = app()->make(resusBankConfigContract::class);
    }

    public function send(array $body): Response
    {
        return Http::withBasicAuth($this->resusBankConfigContract->getAuthUsername(), $this->resusBankConfigContract->getAuthPassword())
            ->post($this->resusBankConfigContract->getMerchantURL(), $body);
    }


}
