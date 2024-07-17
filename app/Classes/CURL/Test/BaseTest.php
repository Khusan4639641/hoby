<?php

namespace App\Classes\CURL\test;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

class Basetest
{

    protected $url;
    private $login;
    private $password;
    protected $requestBody = [];
    protected $responseBody = [];

    public function __construct()
    {
        $this->url      = Config::get('test.test_api_url_rpc_payments');
        $this->login    = Config::get('test.test_api_url_rpc_login');
        $this->password = Config::get('test.test_api_url_rpc_password');
    }

    protected function route() :string
    {
        return $this->url;
    }

    protected function addParamByKey($key, $value)
    {
        $this->requestBody['params'][$key] = $value;
    }

    protected function addParamsByKey(array $keysAndValues)
    {
        foreach($keysAndValues as $key => $value)
            $this->requestBody['params'][$key] = $value;
    }

    protected function addParam($value)
    {
        $this->requestBody['params'][] = $value;
    }

    protected function addParamToRequest($key, $value)
    {
        $this->requestBody[$key] = $value;
    }

    protected function addParamsToRequest(array $keysAndValues)
    {
        foreach($keysAndValues as $key => $value)
            $this->requestBody[$key] = $value;
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

    protected function makeHeaders(): array
    {
        return [
            'Content-type' => 'Application/json'
        ];
    }

    public function execute()
    {
        $this->responseBody = Http::withHeaders($this->makeHeaders())
            ->withoutVerifying()
            ->withBasicAuth($this->login, $this->password)
            ->post($this->url, $this->requestBody);
        return $this;
    }

    public function response()
    {
        return $this->responseBody->json();
    }
}
