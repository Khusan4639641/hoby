<?php

namespace App\Classes\CURL\Katm\Accounting;

use App\Classes\Exceptions\KatmException;

abstract class KatmAccounting extends BaseRequest
{

    private string $login;
    private string $password;
    private string $head;
    private string $code;

    public function __construct()
    {
        $this->baseUrl = config('test.katm_accounting.base_url');
        $this->login = config('test.katm_accounting.login');
        $this->password = config('test.katm_accounting.password');
        $this->head = config('test.katm_accounting.head');
        $this->code = config('test.katm_accounting.code');
        $this->makeRequest();
    }

    protected function setAmountFormat($sum): int
    {
        // Do not remove 'round' method
        return round($sum * 100);
    }

    protected function makeRequest()
    {
        $this->requestBody = [
            'security' => [
                'pLogin' => $this->login,
                'pPassword' => $this->password,
            ],
            'data' => [
                'pHead' => $this->head,
                'pCode' => $this->code,
            ]
        ];
    }

    protected function addParam($key, $value): void
    {
        $this->requestBody['data'][$key] = $value;
    }

    /**
     * @throws KatmException
     */
    protected function updateStatus()
    {
        $this->state = self::FAILED;
        if (!$this->responseBody->successful()) {
            $body = '';
            if ($this->responseBody !== null) {
                $body = $this->responseBody->json();
            }
            throw new KatmException("Ответ от сервиса не удовлетворителен", $this->url(), $this->requestBody, ['status' => $this->responseBody->status(), 'response' => $body]);
        }
        $data = $this->response()->json();
        if (!isset($data['data'])) {
            throw new KatmException("Элемент data не найден. Не возможно получить статус ответа", $this->url(), $this->requestBody, $data);
        }
        if (!isset($data['data']['result'])) {
            throw new KatmException("Элемент data->result не найден. Не возможно получить статус ответа", $this->url(), $this->requestBody, $data);
        }
        if (!(is_string($data['data']['result']) || is_int($data['data']['result']))) {
            throw new KatmException("Элемент data->result имеет невалидный тип. Не возможно получить статус ответа", $this->url(), $this->requestBody, $data);
        }
        if ((string)$data['data']['result'] !== self::KATM_STATUS_SUCCESS) {
            throw new KatmException("Ответ от сервиса вернул код: " . $data['data']['result'], $this->url(), $this->requestBody, $data);
        }
        $this->state = self::SUCCESS;
    }

}
