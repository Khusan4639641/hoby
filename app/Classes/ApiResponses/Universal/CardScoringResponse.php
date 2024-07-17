<?php

namespace App\Classes\ApiResponses\Universal;

use App\Classes\ApiResponses\BaseResponse;
use App\Classes\Exceptions\UniversalException;

class CardScoringResponse extends BaseResponse
{

    const ARRAY_KEY_FORMAT = 'M-Y';

    private function salaryUpdate($amount): float
    {
        return $amount / 100;
    }

    /**
     * @throws UniversalException
     */
    public function receipts(int $maxMonths = 12): array
    {
        $data = $this->json();
        if (!isset($data['result'])) {
            throw new UniversalException("Элемент result не найден", "", [], $data);
        }
        $resultData = $data['result'];
        $newData = [];
        for ($i = 0; $i < $maxMonths; $i++) {
            $key = date(self::ARRAY_KEY_FORMAT, strtotime(date('Y-m-15') . " -" . $i . " month"));
            if (isset($resultData[$key])) {
                $newData[$key] = $this->salaryUpdate($resultData[$key]);
            } else {
                $newData[$key] = 0;
            }
        }
        $remKey = date(self::ARRAY_KEY_FORMAT);
        if (isset($newData[$remKey])) {
            unset($newData[$remKey]);
        }
        return $newData;
    }

}
