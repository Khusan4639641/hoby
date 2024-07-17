<?php

namespace App\Classes\CURL\Universal;

use App\Classes\ApiResponses\BaseResponse;
use App\Classes\CURL\Interfaces\IRequest;
use App\Classes\Exceptions\UniversalException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseUniversalRequest implements IRequest
{

    const SUCCESS = 1;
    const FAILED = 2;
    const AWAIT = 3;

    protected string $baseUrl;
    protected string $token = '';

    protected $state = self::AWAIT;
    protected $requestBody = [];
    protected Response $responseBody;

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
            'id' => 'test_' . uniqid(rand(), 1),
            'method' => $method,
            'params' => [],
        ];
    }

    private function makeHeaders(): array
    {
        return [
            'Unisoft-Authorization' => 'Bearer ' . $this->token,
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
        return $this->responseBody->successful();
    }

    /**
     * @throws UniversalException
     */
    private function updateStatus()
    {
        $this->state = self::FAILED;
        if ($this->responseBody == null) {
            throw new UniversalException("Ответ от сервиса не удовлетворителен", $this->url(), $this->requestBody);
        }
        $data = $this->response()->text();
        if (!$this->responseBody->successful()) {
            throw new UniversalException("Ответ от сервиса не удовлетворителен", $this->url(), $this->requestBody, ['status' => $this->responseBody->status(), 'response' => $data]);
        }
        $data = $this->response()->json();
        if (isset($data['status'])) {
//            throw new testException("Элемент status не найден", $this->url(), $this->requestBody, $data);
            if ($data['status'] == false) {
                throw new UniversalException("Ответ от сервиса вернул отрицательный статус", $this->url(), $this->requestBody, $data);
            }
        }
        $this->state = self::SUCCESS;
    }

    public function execute()
    {
        $response = Http::withHeaders($this->makeHeaders())
            ->post($this->baseUrl, $this->requestBody);
        $this->responseBody = $response;
        $this->updateStatus();
        return $this;
    }

    public function response(): BaseResponse
    {
        return new BaseResponse($this->responseBody->json());
    }

}
