<?php

namespace App\Classes\ApiResponses\MLScore;

use \App\Classes\ApiResponses\BaseResponse;

class MLScoreLoginResponse extends BaseResponse
{

    /**
     * @throws \Exception
     */
    public function token(): string
    {
        $data = $this->json();
        if (!isset($data['access_token'])) {
            throw new \Exception("Элемент access_token не найден", "", $data);
        }
        return $data['access_token'];
    }

}
