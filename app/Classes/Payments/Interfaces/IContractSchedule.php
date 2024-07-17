<?php

namespace App\Classes\Payments\Interfaces;

interface IContractSchedule
{

    public function getID(): int;

    public function debt(): float;

    public function pay(float $amount);

}
