<?php

namespace App\Classes\Scoring;

class ScoreCalculate
{

    private $data;
    private $maxMonths;

/*
        SCORE              LIMIT

       350 000,00 	=    1 000 000,00
     1 000 000,00 	=    3 000 000,00
     3 000 000,00 	=    6 000 000,00
     4 000 000,00 	=    9 000 000,00
     5 000 000,00 	=   12 000 000,00
     7 000 000,00 	=   15 000 000,00
*/


    // amount in kopecks
    private $score = [
        1 => 35000000,
        2 => 75000000,
        3 => 200000000,
        4 => 300000000,
        5 => 400000000,
        6 => 500000000
    ];

    private $limit = [
        1 => 1000000,
        2 => 3000000,
        3 => 6000000,
        4 => 9000000,
        5 => 12000000,
        6 => 15000000
    ];

    public function __construct(array $data, int $maxMonths = 3)
    {
        $this->maxMonths = $maxMonths;
        $this->data = $data;
    }

    private function calculateScore($data, $maxScore)
    {
        if (count($this->data) < $this->maxMonths) {
            return 0;
        }
        $sum = 0;
        foreach ($data as $item) {
            $sum += (int)($item >= $maxScore);
        }
        return $sum;
    }

    public function getBall()
    {
        if (count($this->data) < $this->maxMonths) {
            return 0;
        }
        for ($i = 6; $i > 0; $i--) {
            $ball = $this->calculateScore($this->data, $this->score[$i]);
            if ($ball > 2) {
                return $ball;
            }
        }
        return 0;
    }

    public function getScore()
    {
        for ($i = 6; $i > 0; $i--) {
            $ball = $this->calculateScore($this->data, $this->score[$i]);
            if ($ball > 2) {
                return $this->limit[$i];
            }
        }
        return 0;
    }

}
