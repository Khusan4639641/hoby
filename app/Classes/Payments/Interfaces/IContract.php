<?php

namespace App\Classes\Payments\Interfaces;

interface IContract
{

    public function getID(): int;

    public function getOrderID(): int;

    public function debt(): float;

    public function pay(float $amount);

    public function isValid(): bool;

    public function unpaidSchedule(): IContractSchedule;

    public function unpaidScheduleCount(): int;

}
