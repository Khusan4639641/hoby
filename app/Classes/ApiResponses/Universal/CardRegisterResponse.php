<?php

namespace App\Classes\ApiResponses\Universal;

use App\Classes\ApiResponses\BaseResponse;
use App\Classes\Exceptions\UniversalException;

class CardRegisterResponse extends BaseResponse
{

    const AMOUNT_SUM_DIVIDER = 100;

    /**
     * @throws UniversalException
     */
    public function cardNumber(): string
    {
        $data = $this->json();
        if (!isset($data['result'])) {
            throw new UniversalException("Элемент result не найден", "", [], $data);
        }
        if (!isset($data['result']['card_number'])) {
            throw new UniversalException("Элемент card_number не найден", "", [], $data);
        }
        return $data['result']['card_number'];
    }

    /**
     * @throws UniversalException
     */
    public function expire(): string
    {
        $data = $this->json();
        if (!isset($data['result'])) {
            throw new UniversalException("Элемент result не найден", "", [], $data);
        }
        if (!isset($data['result']['expire'])) {
            throw new UniversalException("Элемент expire не найден", "", [], $data);
        }
        return $data['result']['expire'];
    }

    /**
     * @throws UniversalException
     */
    public function phone(): string
    {
        $data = $this->json();
        if (!isset($data['result'])) {
            throw new UniversalException("Элемент result не найден", "", [], $data);
        }
        if (!isset($data['result']['phone'])) {
            throw new UniversalException("Элемент phone не найден", "", [], $data);
        }
//        return preg_replace('/[^0-9]/', '', $data['result']['phone']);
        return $data['result']['phone'];
    }

    /**
     * @throws UniversalException
     */
    public function balance(): float
    {
        $data = $this->json();
        if (!isset($data['result'])) {
            throw new UniversalException("Элемент result не найден", "", [], $data);
        }
        if (!isset($data['result']['balance'])) {
            throw new UniversalException("Элемент balance не найден", "", [], $data);
        }
        return round($data['result']['balance'] / self::AMOUNT_SUM_DIVIDER, 2);
    }

}
