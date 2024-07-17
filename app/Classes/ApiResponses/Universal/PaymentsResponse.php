<?php

namespace App\Classes\ApiResponses\Universal;

use App\Classes\ApiResponses\BaseResponse;
use App\Classes\Exceptions\UniversalException;

class PaymentsResponse extends BaseResponse
{

    /**
     * @throws UniversalException
     */
    public function paymentID(): string
    {
        $data = $this->json();
        if (!isset($data['result'])) {
            throw new UniversalException("Элемент result не найден", "", [], $data);
        }
        if (!isset($data['result']['payment'])) {
            throw new UniversalException("Элемент payment не найден", "", [], $data);
        }
        if (!isset($data['result']['payment']['id'])) {
            throw new UniversalException("Элемент result -> payment -> id не найден", "", [], $data);
        }
        return $data['result']['payment']['id'];
    }

    /**
     * @throws UniversalException
     */
    public function paymentUUID(): string
    {
        $data = $this->json();
        if (!isset($data['result'])) {
            throw new UniversalException("Элемент result не найден", "", [], $data);
        }
        if (!isset($data['result']['payment'])) {
            throw new UniversalException("Элемент payment не найден", "", [], $data);
        }
        if (!isset($data['result']['payment']['uuid'])) {
            throw new UniversalException("Элемент result -> payment -> uuid не найден", "", [], $data);
        }
        return $data['result']['payment']['uuid'];
    }

}
