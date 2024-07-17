<?php

namespace App\Classes\ApiResponses\Katm;

use App\Classes\Exceptions\KatmException;

class KatmResponseClientAddress extends KatmResponse
{

    /**
     * @throws KatmException
     */
    public function address(): string
    {
        $data = $this->json();
        if (!isset($data['data'])) {
            throw new KatmException("Элемент data не найден", "", [], $data);
        }
        if (!isset($data['data']['address'])) {
            throw new KatmException("Элемент address не найден", "", [], $data);
        }
        return $data['data']['address'];
    }

}
