<?php

namespace App\Classes\Scoring\test\Converter;

abstract class BaseConverter
{

    const ARRAY_KEY_FORMAT = 'M-Y';

    protected array $responseData;

    protected function salaryUpdate($amount): float
    {
        return $amount / 100;
    }

    public function __construct(array $responseData)
    {
        $this->responseData = $responseData;
    }

    abstract public function receipts(int $maxMonths = 12): array;

}
