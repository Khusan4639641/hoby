<?php

namespace App\Classes\CURL\test;

use App\Classes\ApiResponses\BaseResponse;
use App\Classes\Exceptions\testException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

abstract class BasetestRequest
{

    const SUCCESS = 1;
    const FAILED = 2;
    const AWAIT = 3;

    protected string $baseUrl;
    private string $login = '';
    private string $password = '';

    protected $state;
    protected $requestBody = [];
    protected Response $responseBody;

    public function __construct()
    {
        $this->state = self::AWAIT;
        $this->baseUrl = config('test.test_api_base_url');
        $this->login = config('test.test_api_login');
        $this->password = config('test.test_api_password');
    }

    protected function url(): string
    {
        return $this->baseUrl;
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
            'id' => 'api_card_balance_' . time() . uniqid(rand(), 10),
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
        return $this->state == self::SUCCESS;
    }

    /**
     * @throws testException
     */
    protected function updateStatus()
    {
        $this->state = self::FAILED;
        if ($this->responseBody == null) {
            throw new testException("Ответ от сервиса не удовлетворителен", $this->url(), $this->requestBody);
        }
        $data = $this->response()->text();
        if (!$this->responseBody->successful()) {
            throw new testException("Ответ от сервиса не удовлетворителен", $this->url(), $this->requestBody, ['status' => $this->responseBody->status(), 'response' => $data]);
        }
        $data = $this->response()->json();
        if (isset($data['status'])) {
//            throw new testException("Элемент status не найден", $this->url(), $this->requestBody, $data);
            if ($data['status'] == false) {
                throw new testException("Ответ от сервиса вернул отрицательный статус", $this->url(), $this->requestBody, $data);
            }
        }
        $this->state = self::SUCCESS;
    }

    public function execute()
    {
        $response = Http::withBasicAuth($this->login, $this->password)
            ->withoutVerifying()
            ->post($this->url(), $this->requestBody);
        $this->responseBody = $response;
        $this->updateStatus();
        return $this;
    }

    public function response(): BaseResponse
    {
        return new BaseResponse($this->responseBody->json());
    }

}
