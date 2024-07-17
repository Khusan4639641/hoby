<?php

namespace App\Classes\Universal;

use Illuminate\Support\Facades\Http;

class BaseUniversal
{

    const ENV_URL = '';
    const ENV_TOKEN = '';

    private $url;
    private $token;

    protected $requestBody = [];
    protected $responseBody = [];

    public function __construct()
    {
        $this->url = env(static::ENV_URL);
        $this->token = env(static::ENV_TOKEN);
    }

    protected function addParamByKey($key, $value)
    {
        $this->requestBody['params'][$key] = $value;
    }

    protected function addParam($value)
    {
        $this->requestBody['params'][] = $value;
    }

    protected function makeRequest($method)
    {
        $this->requestBody = [
            'jsonrpc' => '2.0',
            'id' => null,
            'method' => $method,
            'params' => [],
        ];
    }

    private function makeHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Content-type' => 'Application/json ',
            'Accept' => 'Application/json ',
        ];
    }

    public function execute()
    {
        $this->responseBody = Http::withHeaders($this->makeHeaders())
            ->post($this->url, $this->requestBody);
        return $this;
    }

    public function response()
    {
        return $this->responseBody->json();
    }

}
