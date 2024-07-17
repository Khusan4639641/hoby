<?php

namespace App\Classes\Card;

class CardType
{

    private string $number;

    public function __construct(string $number)
    {
        $this->number = $number;
    }

    public function isHumo(): bool
    {
        return substr($this->number, 0, 4) == "9860";
    }

    public function isUzcard(): bool
    {
        return substr($this->number, 0, 4) == "8600";
    }

}
