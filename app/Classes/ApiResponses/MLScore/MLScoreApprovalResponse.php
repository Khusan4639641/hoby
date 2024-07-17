<?php

namespace App\Classes\ApiResponses\MLScore;

use \App\Classes\ApiResponses\BaseResponse;

class MLScoreApprovalResponse extends BaseResponse
{

    /**
     * @throws \Exception
     */
    public function isApproved(): bool
    {
        $data = $this->json();
        if (!isset($data['result'])) {
            throw new \Exception("Элемент result не найден", "", $data);
        }
        if (!isset($data['result']['approved'])) {
            throw new \Exception("Элемент approved не найден", "", $data);
        }
        return $data['result']['approved'];
    }

}
