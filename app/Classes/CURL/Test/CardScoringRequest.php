<?php

namespace App\Classes\CURL\test;

use App\Classes\ApiResponses\test\CardScoringResponse;
use App\Services\KeycloakTokenService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class CardScoringRequest extends BasetestRequest
{
    protected KeycloakTokenService $cloak_token;

    public function __construct(string $token)
    {
        parent::__construct();
        $month = config('test.scoring_max_month');
        $this->makeRequest('card.scoring');
        $this->addParamByKey('card_token', $token);
        $this->addParamByKey('start_date', Carbon::now()->subMonth($month)->format('Ym01'));
        $this->addParamByKey('end_date', Carbon::now()->format('Ymd'));
        $this->cloak_token = new KeycloakTokenService();
        $this->baseUrl = config('test.test_api_scoring_base_url');
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
        return $this->baseUrl . '/history/scoring';
    }

    public function response(): CardScoringResponse
    {
        return new CardScoringResponse($this->responseBody->json());
    }

}
