<?php

namespace App\DTO\V3\Account;

class Account1cDTO
{
    public string $number;
    public string $name;
    public bool $is_subconto;
    public ?string $subconto_number;
    public ?int $type;
    public ?string $system_number;
    public bool $is_subconto_without_remainder;

    public function __construct(string $number, string $name, bool $is_subconto, string $subconto_number = null, int $type = null, string $system_number = null, bool $is_subconto_without_remainder = false)
    {
        $this->number = $number;
        $this->name = $name;
        $this->is_subconto = $is_subconto;
        $this->subconto_number = $subconto_number;
        $this->type = $type;
        $this->system_number = $system_number;
        $this->is_subconto_without_remainder = $is_subconto_without_remainder;
    }
}
