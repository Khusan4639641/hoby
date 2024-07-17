<?php

namespace App\Classes\Scoring\test;

class TopScore
{

    const ROUNDING = 100000;

    private float $min;
    private float $max;
    private array $scores = [];

    public function __construct(float $min, float $max)
    {
        $this->min = $min;
        $this->max = $max;
    }

    public function add(float $score)
    {
        $this->scores[] = $score;
    }

    public function result(): float
    {
        $maxScore = 0;
        foreach ($this->scores as $score) {
            if ($maxScore < $score) {
                $maxScore = $score;
            }
        }
        $maxScore = round($maxScore / self::ROUNDING) * self::ROUNDING;
        if ($maxScore < $this->min) {
            $maxScore = 0;
        }
        if ($maxScore > $this->max) {
            $maxScore = $this->max;
        }
        return $maxScore;
    }

}
