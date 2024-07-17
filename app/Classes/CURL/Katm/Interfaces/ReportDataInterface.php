<?php

namespace App\Classes\CURL\Katm\Interfaces;

interface ReportDataInterface
{

    public function __construct(array $data);

    public function getRequestData(): array;

    public function getRequestText(): string;

}
