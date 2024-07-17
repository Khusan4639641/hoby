<?php

namespace App\Classes\ApiResponses\test;

use App\Classes\ApiResponses\BaseResponse;
use App\Classes\Exceptions\testException;

class CardRegisterResponse extends BaseResponse
{

    const AMOUNT_SUM_DIVIDER = 100;

    /**
     * @throws testException
     */
    public function cardNumber(): string
    {
        $data = $this->json();
        if (!isset($data['result'])) {
            throw new testException("Элемент result не найден", "", [], $data);
        }
        if (!isset($data['result']['card_number'])) {
            throw new testException("Элемент card_number не найден", "", [], $data);
        }
        return $data['result']['card_number'];
    }

    /**
     * @throws testException
     */
    public function expire(): string
    {
        $data = $this->json();
        if (!isset($data['result'])) {
            throw new testException("Элемент result не найден", "", [], $data);
        }
        if (!isset($data['result']['expire'])) {
            throw new testException("Элемент expire не найден", "", [], $data);
        }
        return $data['result']['expire'];
    }

    /**
     * @throws testException
     */
    public function phone(): string
    {
        $data = $this->json();
        if (!isset($data['result'])) {
            throw new testException("Элемент result не найден", "", [], $data);
        }
        if (!isset($data['result']['phone'])) {
            throw new testException("Элемент phone не найден", "", [], $data);
        }
//        return preg_replace('/[^0-9]/', '', $data['result']['phone']);
        return $data['result']['phone'];
    }

    /**
     * @throws testException
     */
    public function balance(): float
    {
        $data = $this->json();
        if (!isset($data['result'])) {
            throw new testException("Элемент result не найден", "", [], $data);
        }
        if (!isset($data['result']['balance'])) {
            throw new testException("Элемент balance не найден", "", [], $data);
        }
        return round($data['result']['balance'] / self::AMOUNT_SUM_DIVIDER, 2);
    }

}
