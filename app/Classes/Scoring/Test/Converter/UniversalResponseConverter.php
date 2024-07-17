<?php

namespace App\Classes\Scoring\test\Converter;

class UniversalResponseConverter extends BaseConverter
{

    public function receipts(int $maxMonths = 3): array
    {
        $salaryData = $this->responseData['result'];
        $receipts = [];
        for ($i = 1; $i <= $maxMonths; $i++) {
            $key = date(self::ARRAY_KEY_FORMAT, strtotime(date('Y-m-15') . " -" . $i . " month"));
            $receipt = 0;
            foreach ($salaryData as $salaryKey => $salaryItem) {
                if ($key == $salaryKey) {
                    $receipt = $this->salaryUpdate($salaryItem);
                }
            }
            $receipts[$i] = $receipt;
        }
        return $receipts;
    }
}
