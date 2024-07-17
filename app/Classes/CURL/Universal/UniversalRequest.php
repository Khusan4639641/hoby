<?php

namespace App\Classes\CURL\Universal;

use App\Classes\ApiResponses\BaseResponse;
use App\Classes\Card\CardType;
use App\Classes\CURL\Interfaces\IRequest;

abstract class UniversalRequest implements IRequest
{

    protected IRequest $source;
    protected CardType $cardType;

    public function __construct(string $number)
    {
        $this->cardType = new CardType($number);
    }

    public function requestArray(): array
    {
        return $this->source->requestArray();
    }

    public function requestText(): string
    {
        return $this->source->requestText();
    }

    public function isSuccessful(): bool
    {
        return $this->source->isSuccessful();
    }

    public function execute()
    {
        $this->source->execute();
    }

    public function response(): BaseResponse
    {
        return $this->source->response();
    }

}
