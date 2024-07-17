<?php

namespace App\Classes\CURL\Katm\Accounting;

use App\Classes\ApiResponses\BaseResponse;
use App\Classes\Exceptions\KatmException;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

abstract class BaseRequest
{

    private const TIMEOUT = 30; // in seconds
    protected const SUCCESS = 1;
    protected const FAILED = 2;
    protected const KATM_STATUS_SUCCESS = '05000';

    protected $state;

    protected string $baseUrl;

    protected $requestBody = [];
    protected Response $responseBody;

    private function makeHeaders(): array
    {
        return [
            'Content-type' => 'Application/json ',
            'Accept' => 'Application/json ',
        ];
    }

    public function url(): string
    {
        return $this->baseUrl;
    }

    public function getRequestData(): array
    {
        return $this->requestBody;
    }

    public function getRequestText(): string
    {
        return json_encode($this->requestBody);
    }

    protected function convertDate(string $date): string
    {
        return Carbon::parse($date)->toIso8601ZuluString("millisecond");
    }

    public function isSuccessful(): bool
    {
        return $this->state === self::SUCCESS;
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
        if (!isset($data['result'])) {
            throw new KatmException("Элемент result не найден. Не возможно получить статус ответа", $this->url(), $this->requestBody, $data);
        }
        if (!isset($data['result']['code'])) {
            throw new KatmException("Элемент result->code не найден. Не возможно получить статус ответа", $this->url(), $this->requestBody, $data);
        }
        if (!(is_string($data['result']['code']) || is_int($data['result']['code']))) {
            throw new KatmException("Элемент result->code имеет невалидный тип. Не возможно получить статус ответа", $this->url(), $this->requestBody, $data);
        }
        if ((string)$data['result']['code'] !== self::KATM_STATUS_SUCCESS) {
            throw new KatmException("Ответ от сервиса вернул код: " . $data['result']['code'], $this->url(), $this->requestBody, $data);
        }
        $this->state = self::SUCCESS;
    }

    /**
     * @throws KatmException
     */
    public function execute(): BaseRequest
    {
        $response = Http::timeout(self::TIMEOUT)->withHeaders($this->makeHeaders())
            ->post($this->url(), $this->requestBody);
        if (!$response->successful()) {
            throw new KatmException("Ответ от сервиса не удовлетворителен", $this->url(), $this->requestBody, ['status' => $response->status(), 'response' => $response->body()]);
        }
        $this->responseBody = $response;
        $this->updateStatus();
        return $this;
    }

    public function response(): BaseResponse
    {
        return new BaseResponse($this->responseBody->json());
    }

}
