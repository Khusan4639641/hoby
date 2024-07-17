<?php

namespace App\Classes\CURL\Katm;

use App\Classes\ApiResponses\Katm\KatmResponse;
use App\Classes\Exceptions\KatmException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class BaseKatmRequest
{

    const SUCCESS = 1;
    const FAILED = 2;
    const AWAIT = 3;
    const REPEAT = 4;
    const NOT_FOUND = 5;
    const HIGH_REQUEST_RATE = 6;

    const KATM_STATUS_SUCCESS = '05000';
    const KATM_STATUS_AWAIT = '05050';
    const KATM_STATUS_NOT_FOUND = '00004';
    const KATM_STATUS_HIGH_REQUEST_RATE = '05003';

    protected $state;

//    protected int $updateState = 1;
    protected int $updateState = 0;

    protected string $baseUrl;

    protected string $login;
    protected string $password;

    protected $requestBody = [];
    protected Response $responseBody;

    public function __construct()
    {
        $this->state = self::AWAIT;
        $this->baseUrl = config('test.katm_api_base_url');
        $this->login = $this->getLogin();
        $this->password = $this->getPassword();
        $this->makeRequest();
    }

    protected function getLogin(): string
    {
        return config('test.katm_api_login');
    }

    protected function getPassword(): string
    {
        return config('test.katm_api_password');
    }

    public function url(): string
    {
        return $this->baseUrl;
    }

    protected function makeRequest()
    {
        $this->requestBody = [
            'security' => [
                'pLogin' => $this->login,
                'pPassword' => $this->password,
            ],
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

    private function makeHeaders(): array
    {
        return [
            'Content-type' => 'Application/json ',
            'Accept' => 'Application/json ',
        ];
    }

    public function isSuccessful(): bool
    {
        return $this->state == self::SUCCESS;
    }

    public function isNeedToRepeat(): bool
    {
        return $this->state == self::REPEAT;
    }

    public function isNotFound(): bool
    {
        return $this->state == self::NOT_FOUND;
    }

    public function isHighRequestRate(): bool
    {
        return $this->state == self::HIGH_REQUEST_RATE;
    }

    /**
     * @throws KatmException
     */
    private function updateStatus()
    {
        $this->state = self::FAILED;
        if (!$this->responseBody->successful()) {
            $body = '';
            if ($this->responseBody != null) {
                $body = $this->responseBody->json();
            }
            throw new KatmException("Ответ от сервиса не удовлетворителен", $this->url(), $this->requestBody, ['status' => $this->responseBody->status(), 'response' => $body]);
        }
        $data = $this->response()->json();
        if (!isset($data['data'])) {
            throw new KatmException("Элемент data не найден", $this->url(), $this->requestBody, $data);
        }
        if (!isset($data['data']['result'])) {
            throw new KatmException("Элемент result не найден", $this->url(), $this->requestBody, $data);
        }
        if ($data['data']['result'] == self::KATM_STATUS_AWAIT) {
            $this->state = self::REPEAT;
            return;
        }
        if ($data['data']['result'] == self::KATM_STATUS_NOT_FOUND) {
            $this->state = self::NOT_FOUND;
            return;
        }
        if ($data['data']['result'] == self::KATM_STATUS_HIGH_REQUEST_RATE) {
            $this->state = self::HIGH_REQUEST_RATE;
            return;
        }
        if ($data['data']['result'] != self::KATM_STATUS_SUCCESS) {
            throw new KatmException("Ответ от сервиса вернул код: " . $data['data']['result'], $this->url(), $this->requestBody, $data);
        }
        $this->state = self::SUCCESS;
    }

    /**
     * @throws KatmException
     */
    public function execute()
    {
        $response = Http::withHeaders($this->makeHeaders())
            ->post($this->url(), $this->requestBody);
        $this->responseBody = $response;
        $this->updateStatus();
        return $this;
    }

    public function response(): KatmResponse
    {
        return new KatmResponse($this->responseBody->json());
    }

}
