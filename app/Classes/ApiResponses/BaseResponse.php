<?php

namespace App\Classes\ApiResponses;

class BaseResponse
{

    protected array $data;
    protected string $failedMessage = '';

    public function __construct(array $response)
    {
        $this->data = $response;
    }

    public function text(): string
    {
        return json_encode($this->data);
    }

    public function json(): array
    {
        return $this->data;
    }

    public function failedMessage(): string
    {
        return $this->failedMessage;
    }

}
