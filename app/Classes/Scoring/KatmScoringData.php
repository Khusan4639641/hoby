<?php

namespace App\Classes\Scoring;

class KatmScoringData
{

    private $data;
    private $averageAmount;

    public function __construct(array $data, int $averageAmount = 0)
    {
        $this->data = $data;
        $this->averageAmount = $averageAmount;

        $this->alignToMinimum();
    }

    private function alignToMinimum()
    {
        $min = 0;
        $isFirst = true;
        foreach ($this->data as $item) {
            if ($isFirst) {
                $min = $item;
                $isFirst = false;
            }
            if ($min > $item) {
                $min = $item;
            }
        }
        $min -= $this->averageAmount;
        $newData = [];
        foreach ($this->data as $key => $item) {
            $newData[$key] = $min;
        }
        $this->data = $newData;
    }

    public function result()
    {
        return $this->data;
    }

}
