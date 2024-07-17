<?php

namespace App\Classes\Autopay\UZCARD;

use Illuminate\Support\Facades\Http;

class BaseAutopayRequest
{

    private string $url;
    private string $login;
    private string $password;

    protected $requestBody = [];
    protected $responseBody = [];

    public function __construct()
    {
        $this->url = config('test.autopay_uzcard_url');
        $this->login = config('test.autopay_uzcard_login');
        $this->password = config('test.autopay_uzcard_password');
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
            'id' => time(),
            'method' => $method,
            'params' => [],
        ];
    }

    private function makeHeaders(): array
    {
        return [
            'Content-type' => 'Application/json ',
            'Accept' => 'Application/json ',
        ];
    }

    public function execute()
    {
        $this->responseBody = Http::withHeaders($this->makeHeaders())
            ->withBasicAuth($this->login, $this->password)
            ->post($this->url, $this->requestBody);
        return $this;
    }

    public function status()
    {
        return $this->responseBody->status();
    }

    public function response()
    {
        return $this->responseBody->json();
    }

}
