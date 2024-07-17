<?php


namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

use App\Models\Setting;


class KeycloakTokenService
{
    private const AUTH_TOKEN_TTL = 85800; // 23:50 = 23 часа 50 минут

    private $refresh_url;
    private $client_id;
    private $client_secret;
    private $grant_type;

    public function __construct()
    {
        $this->refresh_url   = config('test.keycloak_service_refresh_url');
        $this->client_id     = config('test.keycloak_service_client_id');
        $this->client_secret = config('test.keycloak_service_client_secret');
        $this->grant_type    = config('test.keycloak_service_grant_type');
    }

    /**
     * @return string
     */
    public function getAuthToken() : string
    {
        if ( Redis::exists("keycloak_service_auth_token") ) {
            return Redis::get("keycloak_service_auth_token");
        }

        return $this->refreshAuthToken();
    }

    /**
     * @return string
     */
    private function refreshAuthToken () : string
    {
        $refreshTokenSetting = Setting::where("param", "keycloak_service_refresh_token")->first();
        $refreshToken        = $refreshTokenSetting->value;

        Log::channel('keycloak')->info("refreshAuthToken() via refresh_token: $refreshToken");

        $response = Http::asForm()->post($this->refresh_url, [
            'client_id'     => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type'    => $this->grant_type,
            'refresh_token' => $refreshToken,
        ]);

        $responseAsArray = $response->json();


        if ( empty($responseAsArray) || ($response->status() !== 200) ) {
            Log::channel('keycloak')->error("KeycloakTokenService refresh token failed, response:\n", ["JSON_response" => $responseAsArray]);
            return "";
        }

        Log::channel('keycloak')->info("Response when refreshAuthToken():\n", ["JSON_response" => $responseAsArray]);

        $authToken     = $responseAsArray["access_token"];
        $authExpiresIn = ( (int) $responseAsArray["expires_in"] ) - 600; // Отнимаем 10 минут, чтобы рефрешнуть раньше.
        $refreshToken  = $responseAsArray["refresh_token"];

        Redis::set("keycloak_service_auth_token", $authToken, 'ex', $authExpiresIn);

        Setting::updateOrCreate(["param" => "keycloak_service_refresh_token"], [ "value" => $refreshToken ]);

        return $authToken;
    }

}
