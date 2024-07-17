<?php

namespace App\Classes\CURL\Katm;

use App\Classes\ApiResponses\Katm\KatmResponseClientAddress;

class KatmRequestClientAddress extends BaseKatmRequest
{

    public function __construct(string $pinfl)
    {
        parent::__construct();
        $this->requestBody['pPin'] = $pinfl;
    }

    public function url(): string
    {
        return $this->baseUrl . 'client/address';
    }

    public function response(): KatmResponseClientAddress
    {
        return new KatmResponseClientAddress($this->responseBody->json());
    }

}
