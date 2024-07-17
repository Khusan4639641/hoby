<?php

namespace App\Classes\CURL\test;

use App\Services\KeycloakTokenService;
use Illuminate\Support\Facades\Http;

class WriteOffCheckRequest extends BasetestRequest
{
    protected KeycloakTokenService $cloak_token;

    public function __construct(int $userID, string $token)
    {
        parent::__construct();
        $this->requestBody['user_id'] = $userID;
        $this->requestBody['card_token'] = $token;
        $this->baseUrl = config('test.test_api_scoring_base_url');
        $this->cloak_token = new KeycloakTokenService();
    }

    public function execute()
    {
        $response = Http::withToken($this->cloak_token->getAuthToken())
            ->withoutVerifying()
            ->post($this->url(), $this->requestBody);
        $this->responseBody = $response;
        $this->updateStatus();
        return $this;
    }

    protected function url(): string
    {
        return $this->baseUrl . '/userpay/initial-pay';
    }

}
