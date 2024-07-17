<?php

namespace App\Services\MFO;

interface AccountInterface
{
    /**
     * @param float $balance
     * @return void
     */
    public function updateBalance(float $balance) : void;

    /**
     * @return string
     */
    public function getNumber() : string;

    /**
     * @return string
     */
    public function getMask() : string;

    /**
     * @return int
     */
    public function getContractId() : int;

    /**
     * @return float
     */
    public function getBalance() : float;
}
