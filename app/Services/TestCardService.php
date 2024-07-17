<?php

namespace App\Services;

use App\Exceptions\KeycloakAuthenticationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http;

class testCardService
{
    private $httpClient;

    private $cardAddUrl;
    private $cardReactivateUrl;
    private $cardConfirmUrl;
    private $getCardBalanceUrl;
    private $getCardInfoUrl;
    private $cardDeleteUrl;
    private $writeOffCheckUrl;

    public function __construct()
    {
        $this->cardAddUrl = config('test.test_api_card_add_url');
        $this->cardReactivateUrl = config('test.test_api_card_reactivate_url');
        $this->cardConfirmUrl = config('test.test_api_card_check_url');
        $this->getCardBalanceUrl = config('test.test_api_get_card_balance_url');
        $this->getCardInfoUrl = config('test.test_api_get_card_info_url');
        $this->cardDeleteUrl = config('test.test_api_card_delete_url');
        $this->writeOffCheckUrl = config('test.user_pay_initial_pay_url');

        $authenticate = config('test.test_api_enable_authentication');

        if ($authenticate) {

            $authToken = (new KeycloakTokenService())->getAuthToken();

            if (!$authToken) {
                throw new KeycloakAuthenticationException();
            }

            $this->httpClient = Http::withToken($authToken)->withOptions([
                'verify' => false,
            ]);

        } else {

            $this->httpClient = Http::withOptions([
                'verify' => false,
            ]);
        }
    }

    public function add(string $pan, string $expiry, string $phone): array
    {
        $response = $this->httpClient->post($this->cardAddUrl, [
            'pan' => $pan,
            'expiry' => $expiry,
            'phoneNumber' => $phone,
        ]);
        $responseAsArray = $response->json() ?? [];

        if ($responseAsArray) {
            Log::channel('cards_v2')->info("Cards Service add (Pan: $pan) response", $responseAsArray);
        } else {
            $httpStatus = $response->status();
            Log::channel('cards_v2')->info("Cards Service add (Pan: $pan) replied with status $httpStatus", [$response->body()]);
        }

        if ($response->successful() && isset($responseAsArray['registrationCode'])) {
            Redis::set(md5($pan), $responseAsArray['registrationCode']);
            return ['status' => 'success'];
        }

        // Send reactivate request if 10011 status code (Card duplicate) returned
        if ($this->isCardDuplicateResponse($response)) {
            return $this->reactivate($pan, $expiry, $phone);
        }

        return $this->processApiError($responseAsArray);
    }

    private function reactivate(string $pan, string $expiry, string $phone): array
    {
        $response = $this->httpClient->post($this->cardReactivateUrl, [
            'pan' => $pan,
            'expiry' => $expiry,
            'phoneNumber' => $phone,
        ]);
        $responseAsArray = $response->json() ?? [];

        if ($responseAsArray) {
            Log::channel('cards_v2')->info("Cards Service reactivate (Pan: $pan) response", $responseAsArray);
        } else {
            $httpStatus = $response->status();
            Log::channel('cards_v2')->info("Cards Service reactivate (Pan: $pan) replied with status $httpStatus", [$response->body()]);
        }

        if ($response->successful() && isset($responseAsArray['registrationCode'])) {
            Redis::set(md5($pan), $responseAsArray['registrationCode']);
            return ['status' => 'success'];
        }

        return $this->processApiError($responseAsArray);
    }

    public function confirm(string $pan, string $code): array
    {
        $registrationCode = Redis::get(md5($pan));
        if (!$registrationCode) return [
            'status' => 'error',
            'message' => __('test_card_service/card.an_error_occurred_please_try_again')
        ];

        $response = $this->httpClient->post($this->cardConfirmUrl, [
            'registrationCode' => $registrationCode,
            'confirmationCode' => $code,
        ]);
        $responseAsArray = $response->json() ?? [];

        if ($responseAsArray) {
            Log::channel('cards_v2')->info("Cards Service confirm (Pan: $pan) response", $responseAsArray);
        } else {
            $httpStatus = $response->status();
            Log::channel('cards_v2')->info("Cards Service confirm (Pan: $pan) replied with status $httpStatus", [$response->body()]);
        }

        if ($response->successful() && isset($responseAsArray['token'])) {
            Redis::del(md5($pan));
            return ['status' => 'success', 'token' => $responseAsArray['token']];
        }

        return $this->processApiError($responseAsArray);
    }

    public function getCardBalance(string $token): array
    {
        $response = $this->httpClient->get($this->getCardBalanceUrl.'/'.$token);
        $responseAsArray = $response->json() ?? [];

        if ($responseAsArray) {
            Log::channel('cards_v2')->info("Cards Service balance (Token: $token) response", $responseAsArray);
        } else {
            $httpStatus = $response->status();
            Log::channel('cards_v2')->info("Cards Service balance (Token: $token) replied with status $httpStatus", [$response->body()]);
        }

        if ($response->successful() && isset($responseAsArray['balance'])) {
            return ['status' => 'success', 'balance' => $responseAsArray['balance']];
        }

        return $this->processApiError($responseAsArray);
    }

    public function getCardInfo(string $token): array
    {
        $response = $this->httpClient->get($this->getCardInfoUrl.'/'.$token);
        $responseAsArray = $response->json() ?? [];

        if ($responseAsArray) {
            Log::channel('cards_v2')->info("Cards Service card info (Token: $token) response", $responseAsArray);
        } else {
            $httpStatus = $response->status();
            Log::channel('cards_v2')->info("Cards Service card info (Token: $token) replied with status $httpStatus", [$response->body()]);
        }

        if ($response->successful() && isset($responseAsArray['phoneNumber'])) {
            return ['status' => 'success', 'card' => $responseAsArray];
        }

        return $this->processApiError($responseAsArray);
    }

    public function writeOffCheck(string $token, int $buyerId): array
    {
        $response = $this->httpClient->post($this->writeOffCheckUrl, [
            'cardToken' => $token,
            'userId' => $buyerId,
        ]);
        $responseAsArray = $response->json() ?? [];

        if ($responseAsArray) {
            Log::channel('cards_v2')->info("Cards Service initial pay (Token: $token) response", $responseAsArray);
        } else {
            $httpStatus = $response->status();
            Log::channel('cards_v2')->info("Cards Service initial pay (Token: $token) replied with status $httpStatus", [$response->body()]);
        }

        if ($response->successful()) {
            return ['status' => 'success'];
        }

        return $this->processApiError($responseAsArray);
    }

    public function delete(string $token): array
    {
        $response = $this->httpClient->delete($this->cardDeleteUrl.'/'.$token);
        $responseAsArray = $response->json() ?? [];

        if ($responseAsArray) {
            Log::channel('cards_v2')->info("Cards Service delete (Token: $token) response", $responseAsArray);
        } else {
            $httpStatus = $response->status();
            Log::channel('cards_v2')->info("Cards Service delete (Token: $token) replied with status $httpStatus", [$response->body()]);
        }

        if ($response->successful()) {
            return ['status' => 'success'];
        }

        return $this->processApiError($responseAsArray);
    }

    private function isCardDuplicateResponse($response): bool
    {
        $responseAsArray = $response->json() ?? [];

        return $response->failed() && isset($responseAsArray['status']) && $responseAsArray['status'] == 10011;
    }

    private function processApiError(array $response): array
    {
        if ( !(isset($response['status']) || isset($response['code'])) ) {
            $errorMessage = __("test_card_service/card.an_error_occurred_please_try_again");
            return ['status' => 'error', 'message' => $errorMessage];
        }

        $errorCode = $response['status'] ?? $response['code'];

        if (Lang::has("test_card_service/code.$errorCode")) {
            $errorMessage = __("test_card_service/code.$errorCode");
        } else {
            $errorMessage = __("test_card_service/card.an_error_occurred_please_try_again");
        }

        return ['status' => 'error', 'message' => $errorMessage];
    }

    private function convertMessageToErrorKey(string $message): string
    {
        // Remove the variable part of error message
        $message = str_contains($message, 'Card was blocked in SVGate') ? 'Card was blocked in SVGate' : $message;

        // Remove special characters
        $message = preg_replace('/[^A-Za-z0-9 ]/','', $message);

        // Make a string lowercase & replace spaces with underscore
        $message = preg_replace('/\s+/', '_', strtolower($message));

        // Card not found! => card_not_found
        return $message;
    }
}
