<?php

namespace App\Classes\Scoring\test;

class Scoring
{

    private bool $isIncreasedToYear = false;

    protected array $receiptsData;
    protected float $amount;

    public function __construct(array $receipts, int $maxMonth = 6)
    {
        $this->receiptsData = array_slice($receipts, 0, $maxMonth);
    }

    public function limitAmount(float $minAmount, float $maxAmount): Scoring
    {
        $newReceiptsData = [];
        foreach ($this->receiptsData as $receiptsItem) {
            $newItem = $receiptsItem;
            if ($receiptsItem < $minAmount) {
                $newItem = 0;
            }
            if ($receiptsItem > $maxAmount) {
                $newItem = $maxAmount;
            }
            $newReceiptsData[] = $newItem;
        }
        $this->receiptsData = $newReceiptsData;
        return $this;
    }

    public function averageAmount(int $minActualMonths = 3): Scoring
    {
        $count = 0;
        foreach ($this->receiptsData as $receiptsItem) {
            if ($receiptsItem == 0 && $count < $minActualMonths) {
                $this->amount = 0;
                return $this;
            }
            $count++;
        }
        $this->amount = array_sum($this->receiptsData) / count($this->receiptsData);
        return $this;
    }

    public function increaseToYear(): Scoring
    {
        if (!$this->isIncreasedToYear) {
            $this->amount *= 12;
            $this->isIncreasedToYear = true;
        }
        return $this;
    }

    public function deductDebt(float $debt): Scoring
    {
        $this->amount -= $debt;
        $this->amount = $this->amount > 0 ? $this->amount : 0;
        return $this;
    }

    public function subtractPercentage(float $percent): Scoring
    {
        $this->amount = $this->amount * $percent / 100;
        return $this;
    }

    public function limit(): float
    {
        return $this->amount;
    }

}
