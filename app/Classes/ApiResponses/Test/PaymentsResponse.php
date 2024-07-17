<?php

namespace App\Classes\ApiResponses\test;

use App\Classes\ApiResponses\BaseResponse;
use App\Classes\Exceptions\testException;

class PaymentsResponse extends BaseResponse
{

    /**
     * @throws testException
     */
    public function paymentID(): string
    {
        $data = $this->json();
        if (!isset($data['result'])) {
            throw new testException("Элемент result не найден", "", [], $data);
        }
        if (!isset($data['result']['payment'])) {
            throw new testException("Элемент payment не найден", "", [], $data);
        }
        if (!isset($data['result']['payment']['id'])) {
            throw new testException("Элемент result -> payment -> id не найден", "", [], $data);
        }
        return $data['result']['payment']['id'];
    }

    /**
     * @throws testException
     */
    public function paymentUUID(): string
    {
        $data = $this->json();
        if (!isset($data['result'])) {
            throw new testException("Элемент result не найден", "", [], $data);
        }
        if (!isset($data['result']['payment'])) {
            throw new testException("Элемент payment не найден", "", [], $data);
        }
        if (!isset($data['result']['payment']['uuid'])) {
            throw new testException("Элемент result -> payment -> uuid не найден", "", [], $data);
        }
        return $data['result']['payment']['uuid'];
    }

}
