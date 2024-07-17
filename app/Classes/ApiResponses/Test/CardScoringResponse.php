<?php

namespace App\Classes\ApiResponses\test;

use App\Classes\ApiResponses\BaseResponse;
use App\Classes\Exceptions\testException;

class CardScoringResponse extends BaseResponse
{

    const ARRAY_KEY_FORMAT = 'M-Y';

    private function salaryUpdate($amount): float
    {
        return $amount / 100;
    }

    public function receipts(int $maxMonths = 12): array
    {
        $data = $this->json();
        if (!isset($data['result'])) {
            return [];
        }
        $receiptsData = $data['result'];
        $receipts = [];
        for ($i = 1; $i <= $maxMonths; $i++) {
            $key = date(self::ARRAY_KEY_FORMAT, strtotime(date('Y-m-15') . " -" . $i . " month"));
            $receipt = 0;
            foreach ($receiptsData as $item) {
                if ($key == $item['date']) {
                    $receipt = $this->salaryUpdate($item['salaries']['amount'] + $item['p2pCredit']['amount']);
                }
            }
            $receipts[$key] = $receipt;
        }
        $remKey = date(self::ARRAY_KEY_FORMAT);
        if (isset($receipts[$remKey])) {
            unset($receipts[$remKey]);
        }
        return $receipts;
    }

    public function checkLastMonthsIncome(): bool
    {
        $data = $this->json();

        if (!isset($data['result'])) {
            return false;
        }

        $result = $data['result'];

        $monthsInterval = config('test.scoring_max_month');

        $monthlyReceiptsSumIsValid = true;
        $currentDay = date('d', time());
        $currentMonthIndex = 0;
        $lastMonthIndex = $monthsInterval;
        $currentMonthIsIncluded = false;
        $monthKeyExistsInResult = [];

        if (count($result) < $monthsInterval) {
            return false;
        }

        // Iterate over required month interval (0-3)
        for ($i = $currentMonthIndex; $i <= $monthsInterval; $i++) {

            // Generate a month key in descending order
            if ($i == $currentMonthIndex) {
                // Start with current month (Mar)
                $monthKey = date('M-Y');
            } else {
                // Next month in descending order (Feb-Jan-Dec)
                $monthKey = date('M-Y', strtotime(date('Y-m-15') . " -" . $i . " month"));
            }

            $monthKeyExistsInResult[$i] = false;

            // Iterate over scoring data, pull out the required month by generated key and check the income sum
            foreach ($result as $monthlyData) {

                if ($monthlyData['date'] == $monthKey) {

                    $monthlyReceipt = ($monthlyData['salaries']['amount'] + $monthlyData['p2pCredit']['amount'])/100;

                    // Check current month income
                    if ($i == $currentMonthIndex) {

                        if ($monthlyReceipt >= 350000) {

                            // Current month income is sufficient. Count its result instead of the last month
                            $currentMonthIsIncluded = true;

                        } elseif ($currentDay >= 25) {

                            // Current month income is not sufficient, card check not passed, break the loop
                            $monthlyReceiptsSumIsValid = false;
                            break 2;
                        }

                        // Current month income is not sufficient, but current day is less than 25. Do nothing

                    } else { // Check previous months

                        if ($i == $lastMonthIndex && $currentMonthIsIncluded) {

                            // Current month result has passed the income check, may skip the last one
                            break 2;
                        }

                        if ($monthlyReceipt < 350000) {

                            // One of the monthly incomes is not sufficient, card check not passed, break the loop
                            $monthlyReceiptsSumIsValid = false;
                            break 2;
                        }
                    }

                    $monthKeyExistsInResult[$i] = true;
                }
            }

            // If month is not present in result
            if (!$monthKeyExistsInResult[$i]) {

                if ($i == $currentMonthIndex && $currentDay < 25) {

                    // Current month is not present, but current day is less than 25. Proceed to the next month
                    continue;

                } elseif ($i == $lastMonthIndex && $currentMonthIsIncluded) {

                    // Last month is not present, but current month is included, card check passed, may break the loop
                    break;

                } else {

                    // A month not included in the above exceptions is not present in result. Break the loop
                    $monthlyReceiptsSumIsValid = false;
                    break;
                }
            }
        }

        return $monthlyReceiptsSumIsValid;
    }
}


