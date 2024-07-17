<?php

namespace App\DTO\V3\Account;

class MFOAccountDTO
{
    public string $number;

    public function __construct(string $number)
    {
        $this->number = $number;
    }
}
