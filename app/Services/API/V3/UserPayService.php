<?php

namespace App\Services\API\V3;

use App\Exceptions\KeycloakAuthenticationException;
use App\Services\KeycloakTokenService;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\{Http, Log};

class UserPayService extends BaseService
{
    private string         $userPayUrl;
    private PendingRequest $httpClient;

    public function __construct()
    {
        $this->userPayUrl = config('test.userpay_api_url');
        if (config('test.test_api_enable_authentication')) {
            $authToken = (new KeycloakTokenService())->getAuthToken();
            if (!$authToken) {
                throw new KeycloakAuthenticationException();
            }
            $this->httpClient = Http::withToken($authToken)->withOptions(['verify' => false]);
        } else {
            $this->httpClient = Http::withOptions(['verify' => false]);
        }
    }

    /**
     * Создание счета в клиринге
     *
     * @param int $userId
     *
     * @return void
     */
    public function createClearingAccount(int $userId): void
    {
        $this->userPayUrl .= 'userpay/initiate-account';
        $response         = $this->httpClient->post($this->userPayUrl, ['user_id' => $userId]);
        // Логируем ошибки, без выбрасывания исключений, чтобы не сломать процесс
        if ($response->failed()) {
            Log::channel('userpay')->error('Ошибка создания ЛС в клиринге',
                                           ['userId' => $userId, 'response' => $response->body()]);
        }
    }

    public function refillUserAccount(float $amount, int $cardId, int $userId): array
    {
        $this->userPayUrl .= 'userpay/account-replenishment';
        $response         = $this->httpClient->post($this->userPayUrl, ['amount'  => $amount,
                                                                        'card_id' => $cardId,
                                                                        'user_id' => $userId]);
        if ($response->failed()) {
            $result = $response->json();
            $errors = __('userpay');
            Log::channel('userpay')->error('Ошибка пополнения ЛС', ['user_id'  => $userId,
                                                                    'card_id'  => $cardId,
                                                                    'response' => $response->body()]);
            if (!(isset($result['status']) || isset($result['code']))) {
                return ['status' => 'error', 'message' => $errors[-1999]];
            }
            if ($result['code'] === -1203 && isset($result['messages']) && str_contains($result['messages'][0], 'nsufficient funds')) {
                return ['status' => 'error', 'message' => $errors['inf_funds']];
            }
            $errorMessage = $errors[$result['code']] ?? $errors[-1999];

            return ['status' => 'error', 'message' => $errorMessage];
        }

        return ['status' => 'success'];
    }
}
