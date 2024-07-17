<?php

namespace App\Classes\CURL\test;

use App\Classes\ApiResponses\test\PaymentsResponse;
use App\Classes\Card\CardType;
use App\Classes\Exceptions\testException;
use Illuminate\Support\Facades\Config;

class PaymentsRequest extends BasetestRequest
{

    const AMOUNT_SUM_MULTIPLIER = 100;

    /**
     * @throws testException
     */
    public function __construct(string $number, string $expire, float $amount)
    {
        parent::__construct();

        $merchant = '';
        $terminal = '';

        $cardType = new CardType($number);

        if ($cardType->isHumo()) {
            $merchant = config('test.test_api_humo_merchant');
            $terminal = config('test.test_api_humo_terminal');
        } else if ($cardType->isUzcard()) {
            $merchant = config('test.test_api_uzcard_merchant');
            $terminal = config('test.test_api_uzcard_terminal');
        }

        $amount *= self::AMOUNT_SUM_MULTIPLIER;
        $amount = (int) round($amount);

        $this->makeRequest('payment');
        $this->addParamByKey('pan', $number);
        $this->addParamByKey('expire', $expire);
        $this->addParamByKey('amount', $amount);
        $this->addParamByKey('terminal', $terminal);
        $this->addParamByKey('merchant', $merchant);
    }

    protected function url(): string
    {
        return $this->baseUrl . '/v1/rpc/payments';
    }

    public function response(): PaymentsResponse
    {
        return new PaymentsResponse($this->responseBody->json());
    }

}
