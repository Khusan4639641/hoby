<?php

namespace App\Classes\Scoring;

class ScoringData
{

    const ARRAY_KEY_FORMAT = 'M-Y';
    const MAX_MISSING_MONTHS = 12;

    private $data;
    private $maxMonths;

    public function __construct(array $data, $borderDay = 20, int $maxMonths = 3)
    {
        $this->data = $data;
        $this->maxMonths = $maxMonths;
        $this->addMissingMonths();
        $this->removeNotActualMonth($borderDay);
        $this->cutExtraMonths();
    }

    private function removeNotActualMonth($borderDay)
    {
        $isCurrentMonthActual = date('d', time()) >= $borderDay;
        $currentMonthKey = date(self::ARRAY_KEY_FORMAT, time());
        if (isset($this->data[$currentMonthKey])) {
            if (!$isCurrentMonthActual) {
                unset($this->data[$currentMonthKey]);
            }
        }
    }

    private function cutExtraMonths()
    {
        $this->data = array_slice($this->data, 0, $this->maxMonths);
    }

    private function addMissingMonths()
    {
        $newData = [];
        for ($i = 0; $i < self::MAX_MISSING_MONTHS; $i++) {
            $key = date(self::ARRAY_KEY_FORMAT, strtotime(date('Y-m-15') . " -" . $i . " month"));
            if (isset($this->data[$key])) {
                $newData[$key] = $this->data[$key];
            } else {
                $newData[$key] = 0;
            }
        }
        $this->data = $newData;
    }

    public function result()
    {
        return $this->data;
    }

}
