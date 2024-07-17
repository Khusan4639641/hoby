<?php

namespace App\Classes\CURL\test;

use App\Classes\ApiResponses\test\CardRegisterResponse;

class CardRequest extends BasetestRequest
{

    public function __construct(string $number, string $expire)
    {
        parent::__construct();
        $this->makeRequest('card.register');
        $this->addParamByKey('number', $number);
        $this->addParamByKey('expiryDate', $expire);
    }

    protected function url(): string
    {
        return $this->baseUrl . '/v1/rpc/cards';
    }

    public function response(): CardRegisterResponse
    {
        return new CardRegisterResponse($this->responseBody->json());
    }

}
