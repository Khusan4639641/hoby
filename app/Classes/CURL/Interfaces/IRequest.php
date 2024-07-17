<?php

namespace App\Classes\CURL\Interfaces;

use App\Classes\ApiResponses\BaseResponse;

interface IRequest
{
    public function requestArray(): array;

    public function requestText(): string;

    public function isSuccessful(): bool;

    public function execute();

    public function response(): BaseResponse;

}
