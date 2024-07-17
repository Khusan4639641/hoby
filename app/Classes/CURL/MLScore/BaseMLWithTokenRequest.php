<?php

namespace App\Classes\CURL\MLScore;

use App\Classes\Exceptions\MLException;
use App\Helpers\TokenCacheHelper;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseMLWithTokenRequest extends BaseMLRequest
{

    const SUCCESS = 1;
    const FAILED = 2;

    protected $state;

    private string $token;

    public function __construct()
    {
        parent::__construct();
        if (TokenCacheHelper::exists()) {
            $this->token = TokenCacheHelper::get();
        } else {
            $this->refreshToken();
        }
    }

    private function executeAndGetResponse(): Response
    {
        return Http::withToken($this->token)
            ->withHeaders($this->makeHeaders())
            ->post($this->url(), $this->requestBody);
    }

    /**
     * @throws MLException
     */
    protected function updateStatus(): void
    {
        $this->state = self::FAILED;
        if (!$this->responseBody->successful()) {
            $body = '';
            if ($this->responseBody != null) {
                $body = $this->responseBody->json();
            }
            throw new MLException(
                "Ответ от сервиса не удовлетворителен",
                $this->url(),
                $this->requestBody,
                $body ?: [],
                $this->responseBody->status()
            );
        }
        $data = $this->response()->json();
        if (!isset($data['success'])) {
            throw new MLException(
                "Элемент success не найден",
                $this->url(),
                $this->requestBody,
                $data ?: [],
                $this->responseBody->status()
            );
        }
        if ($data['success'] === false) {
            throw new MLException(
                "Ошибка от сервиса ML",
                $this->url(),
                $this->requestBody,
                $data ?: [],
                $this->responseBody->status()
            );
        }
        $this->state = self::SUCCESS;
    }


    public function execute()
    {
        $response = $this->executeAndGetResponse();
        if ($response->status() == 403) {
            $this->refreshToken();
            $response = $this->executeAndGetResponse();
        }
        $this->responseBody = $response;
        $this->updateStatus();
        return $this;
    }

    private function refreshToken()
    {
        $this->token = $this->getToken();
        TokenCacheHelper::update($this->token);
    }

    public function isSuccessful(): bool
    {
        return $this->state == self::SUCCESS;
    }

    private function getToken(): string
    {
        $token = "";
        try {
            $request = new MLScoreLogin();
            $request->execute();
            if ($request->isSuccessful()) {
                $token = $request->response()->token();
            } else {
                throw new \Exception("Попытка авторизации в ML сервисе провалилась");
            }
        } catch (\Exception $e) {
            Log::channel('ml_token_expire')->error($e->getMessage());
        }
        return $token;
    }

}
