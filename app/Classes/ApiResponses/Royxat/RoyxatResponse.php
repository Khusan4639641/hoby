<?php

namespace App\Classes\ApiResponses\Royxat;

use App\Classes\ApiResponses\BaseResponse;

class RoyxatResponse extends BaseResponse
{

    const STATUS_BAD = 4;

    public function havingDebts(): bool
    {
        $data = $this->json();
        foreach ($data as $item) {
            if (isset($item['status_id'])) {
                if ($item['status_id'] == self::STATUS_BAD) {
                    return true;
                }
            }
        }
        return false;
    }

}
