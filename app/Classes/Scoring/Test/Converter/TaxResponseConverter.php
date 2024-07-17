<?php

namespace App\Classes\Scoring\test\Converter;

class TaxResponseConverter extends BaseConverter
{

    private function calculateReceipt(float $salary, float $tax): float
    {
//        (salary -  salaryTaxSum) * 1000
        return ($salary - $tax) * 1000;
    }

    /**
     * @throws \Exception
     */
    public function isFired(): bool
    {
        if (!is_bool($this->responseData['scoring']['fired'])) {
            throw new \Exception("'scoring' -> 'fired' element is not bool type");
        }
        return $this->responseData['scoring']['fired'];
    }

    public function receipts(int $maxMonths = 12): array
    {
        $salaryData = $this->responseData['result'];
        $receipts = [];
        for ($i = 1; $i <= $maxMonths; $i++) {
            $year = date('Y', strtotime(date('Y-m-15') . " -" . $i . " month"));
            $month = date('n', strtotime(date('Y-m-15') . " -" . $i . " month"));
            $receipt = 0;
            foreach ($salaryData as $salaryItem) {
                if ($year == $salaryItem['year'] && $month == $salaryItem['period']) {
                    $receipt += $this->calculateReceipt($salaryItem['salary'], $salaryItem['salaryTaxSum']);
                }
            }
            $receipts[$i] = $receipt;
        }
        return $receipts;
    }

}
