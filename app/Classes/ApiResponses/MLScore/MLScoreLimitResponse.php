<?php

namespace App\Classes\ApiResponses\MLScore;

use \App\Classes\ApiResponses\BaseResponse;

class MLScoreLimitResponse extends BaseResponse
{

    /**
     * @throws \Exception
     */
    public function limit(): int
    {
        $data = $this->json();
        if (!isset($data['result'])) {
            throw new \Exception("Элемент result не найден", "", $data);
        }
        if (!isset($data['result']['limit'])) {
            throw new \Exception("Элемент limit не найден", "", $data);
        }
        return $data['result']['limit'];
    }

}
