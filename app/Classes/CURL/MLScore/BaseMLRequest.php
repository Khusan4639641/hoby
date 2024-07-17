<?php

namespace App\Classes\CURL\MLScore;

use App\Classes\ApiResponses\BaseResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class BaseMLRequest
{

    protected string $baseUrl;

    protected $requestBody = [];
    protected Response $responseBody;

    public function __construct()
    {
        $this->init();
    }

    public function init(): void
    {
        $this->baseUrl = config('test.ml.v1.url');
    }

    public function url(): string
    {
        return $this->baseUrl;
    }

    public function requestArray(): array
    {
        return $this->requestBody;
    }

    public function requestText(): string
    {
        return json_encode($this->requestBody) != false ? json_encode($this->requestBody) : '';
    }

    protected function addParamByKey($key, $value)
    {
        $this->requestBody[$key] = $value;
    }

    public function isSuccessful(): bool
    {
        return $this->responseBody->successful();
    }

    protected function makeHeaders(): array
    {
        return [
            'Content-type' => 'Application/json',
            'Accept' => 'Application/json',
        ];
    }

    public function execute()
    {
        $response = Http::withHeaders($this->makeHeaders())
            ->post($this->url(), $this->requestBody);
        $this->responseBody = $response;
        return $this;
    }

    public function response(): BaseResponse
    {
        return new BaseResponse($this->responseBody->json());
    }

}
