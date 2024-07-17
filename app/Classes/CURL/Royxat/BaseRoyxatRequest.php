<?php

namespace App\Classes\CURL\Royxat;

use App\Classes\ApiResponses\Royxat\RoyxatResponse;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class BaseRoyxatRequest
{

    protected string $baseUrl;

    private string $token;

    protected $requestBody = [];
    protected Response $responseBody;

    public function __construct()
    {
        $this->baseUrl = config('test.royxat.url');
        $this->token = config('test.royxat.token');
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

    public function isSuccessful(): bool
    {
        return $this->responseBody->successful();
    }

    private function makeHeaders(): array
    {
        return [
            'Content-type' => 'Application/json',
            'Accept' => 'Application/json',
            'Token' => $this->token,
        ];
    }

    public function execute()
    {
        $response = Http::withHeaders($this->makeHeaders())
            ->get($this->url(), $this->requestBody);
        $this->responseBody = $response;
        return $this;
    }

    public function response(): RoyxatResponse
    {
        return new RoyxatResponse($this->responseBody->json());
    }

}
