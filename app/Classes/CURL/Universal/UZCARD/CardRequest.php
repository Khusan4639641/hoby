<?php

namespace App\Classes\CURL\Universal\UZCARD;

use App\Classes\ApiResponses\Universal\CardRegisterResponse;

class CardRequest extends BaseUzcardRequest
{

    public function __construct(string $number, string $expire)
    {
        parent::__construct();
        $this->makeRequest('card.register');
        $this->addParam([
            'card_number' => $number,
            'expire' => $expire,
        ]);
    }

    public function response(): CardRegisterResponse
    {
        return new CardRegisterResponse($this->responseBody->json());
    }

}
