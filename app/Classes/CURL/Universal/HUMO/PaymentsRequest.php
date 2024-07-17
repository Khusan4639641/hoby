<?php

namespace App\Classes\CURL\Universal\HUMO;

use App\Classes\ApiResponses\Universal\PaymentsResponse;
use Illuminate\Support\Facades\Config;

class PaymentsRequest extends BaseHumoRequest
{

    const AMOUNT_SUM_MULTIPLIER = 100;

    public function __construct(string $number, string $expire, float $amount)
    {
        parent::__construct();

        $merchant = config('test.universal_merchant_id_humo');
        $terminal = config('test.universal_terminal_id_humo');

        $amount *= self::AMOUNT_SUM_MULTIPLIER;
        $amount = (int)round($amount);

        $this->makeRequest('payment');
        $this->addParamByKey('card_number', $number);
        $this->addParamByKey('expire', $expire);
        $this->addParamByKey('amount', $amount);
        $this->addParamByKey('terminal', $terminal);
        $this->addParamByKey('merchant', $merchant);
    }

    public function response(): PaymentsResponse
    {
        return new PaymentsResponse($this->responseBody->json());
    }

}
