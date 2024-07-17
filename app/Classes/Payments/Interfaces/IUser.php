<?php

namespace App\Classes\Payments\Interfaces;

interface IUser
{

    public function userID(): int;

    public function balance(): float;

    public function refill(float $amount);

    public function debit(float $amount): bool;

}
