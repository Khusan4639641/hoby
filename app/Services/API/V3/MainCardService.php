<?php

namespace App\Services\API\V3;

use App\Facades\OldCrypt;
use App\Helpers\CardHelper;
use App\Helpers\EncryptHelper;
use App\Http\Controllers\Core\UniversalController;
use App\Models\Buyer;
use App\Models\Card;
use App\Services\API\V3\Interfaces\CardInterface;
use Illuminate\Http\Request;
use Log;

class MainCardService extends BaseService implements CardInterface
{
    public static function balance(Request $request, Card $theCard, $flag = false): array
    {
        $card = EncryptHelper::decryptData($theCard->card_number);
        $request->merge(['info_card' => $theCard->toArray()]);
        $result = null;
        $card_type = CardHelper::checkTypeCard($card)['type'];
        switch ($card_type) {
            case 1:
                if ($flag) {
                    $result = (new self)->getUzcardBalance($theCard->token);
                } else {
                    $result = UniversalController::getCardInfo($request);
                }
                break;
            case 2:
                if ($flag) {
                    $result = (new self)->getHumoBalance($request);
                } else {
                    $request->merge(['url_humo' => true]);
                    $result = UniversalController::getCardInfo($request);
                }
                break;
            default:
                $result['status'] = 'error';
                $result['message'] = __('card.card_error');
                return $result;
        }
        Log::channel('cards')->info($result);
        return $result;
    }

    private function getUzcardBalance($token)
    {
        $data = [];
        Log::channel('cards')->info('start uzcard.cards.get');
        $token = EncryptHelper::decryptData($token);
        $requestId = 'test_' . uniqid(rand(), 1);
        $jRequest = json_encode([
            'jsonrpc' => '2.0',
            'id' => $requestId,
            'method' => 'cards.get',
            'params' => [
                'ids' => [
                    $token
                ],
            ],
        ]);
        Log::channel('cards')->info(print_r($jRequest, 1));
        $response = CardHelper::requestUZCard($this->buildUzcardAuthorizeConfig(), $jRequest);
        Log::channel('cards')->info(print_r($response, 1));
        if (isset($response['result'][0]['pan'])) {
            $data['status'] = 'success';
            $data['data'] = ['balance' => $response['result'][0]['balance']];
        } else {
            $data['status'] = 'error';
            $data['message'] = $response;
        }
        Log::channel('cards')->info('end uzcard.cards.get');
        return $data;
    }

    private function getHumoBalance(Request $request)
    {
        $data = [];
        $config = $this->buildHumoAuthorizeConfig();
        $client = CardHelper::connectedHumoCard($config, 'balance', false);
        $splitCardInfo = CardHelper::getAdditionalCardInfo(EncryptHelper::decryptData($request->info_card['card_number']), EncryptHelper::decryptData($request->info_card['card_valid_date']));
        $response = CardHelper::requestHumoBalance($client, $splitCardInfo);
        if (isset($response->availableAmount)) {
            $data['status'] = 'success';
            $data['data'] = ['balance' => $response->availableAmount];
        } else {
            $data['status'] = 'error';
            $data['message'] = __('api.internal_error');
        }
        return $data;
    }

    private function buildUzcardAuthorizeConfig(): array
    {
        if (config('app.debug')) {
            $config = [
                'url' => config('test.uz_apiurl_test'),
                'login' => config('test.uz_login_test'),
                'password' => config('test.uz_password_test'),
                'terminal_id' => config('test.uz_terminal_id'),
                'merchant_id' => config('test.uz_merchant_id'),
                'port' => config('test.uz_port'),
            ];
        } else {
            $config = [
                'url' => config('test.uz_apiurl'),
                'login' => config('test.uz_login'),
                'password' => config('test.uz_password'),
                'terminal_id' => config('test.uz_terminal_id'),
                'merchant_id' => config('test.uz_merchant_id'),
                'port' => config('test.uz_port'),
            ];
        }
        return $config;
    }

    private function buildHumoAuthorizeConfig(): array
    {
        if (config('app.debug')) {
            $config = [
                'url_scoring' => config('test.humo_url_test_scoring'),
                'url_balance' => config('test.humo_url_test_balance'),
                'url_phone' => config('test.humo_url_test_phone'),
                'url_discard' => config('test.humo_url_discard_test'),
                'login' => config('test.humo_login_test'),
                'password' => config('test.humo_password_test'),
                'merchant_id' => config('test.humo_merchant_id'),
                'terminal_id' => config('test.humo_terminal_id'),
            ];
        } else {
            $config = [
                'url_scoring' => config('test.humo_url_scoring'),
                'url_balance' => config('test.humo_url_balance'),
                'url_phone' => config('test.humo_url_phone'),
                'url_discard' => config('test.humo_url_discard'),
                'merchant_id' => config('test.humo_merchant_id'),
                'terminal_id' => config('test.humo_terminal_id'),
                'login' => config('test.humo_login'),
                'password' => config('test.humo_password')
            ];
        }
        return $config;
    }

    public static function buildAuthorizeConfig(): array
    {
        $config = [
            'login' => config('test.universal_login'),
            'password' => config('test.universal_password'),

            'url_humo' => config('test.universal_url_humo'),
            'url_humo_balance' => config('test.universal_balance_humo'),
            'url_uzcard' => config('test.universal_url_uzcard'),

            'universal_token_uzcard' => config('test.universal_token_uzcard'),
            'universal_token_humo' => config('test.universal_token_humo'),

            'terminal_id_uzcard' => config('test.universal_terminal_id_uzcard'),
            'merchant_id_uzcard' => config('test.universal_merchant_id_uzcard'),

            'terminal_id_humo' => config('test.universal_terminal_id_humo'),
            'merchant_id_humo' => config('test.universal_merchant_id_humo'),

            'terminal_uzcard_autopay' => config('test.universal_terminal_uzcard_autopay'),
            'merchant_uzcard_autopay' => config('test.universal_merchant_uzcard_autopay'),

            'test_api_balance_switch' => config('test.test_api_balance_switch'),
            'test_api_scoring_switch' => config('test.test_api_scoring_switch'),
            'test_api_payment_and_cancel_switch' => config('test.test_api_payment_and_cancel_switch'),
            'test_api_login' => config('test.test_api_login'),
            'test_api_password' => config('test.test_api_password'),
            'test_api_url_card_balance' => config('test.test_api_url_card_balance'),
            'test_api_url_card_scoring' => config('test.test_api_url_card_scoring'),
            'test_api_url_card_payment' => config('test.test_api_url_card_payment'),
            'test_api_url_card_payment_cancel' => config('test.test_api_url_card_payment_cancel'),

            'test_api_uzcard_merchant' => config('test.test_api_uzcard_merchant'),
            'test_api_uzcard_terminal' => config('test.test_api_uzcard_terminal'),
            'test_api_humo_merchant' => config('test.test_api_humo_merchant'),
            'test_api_humo_terminal' => config('test.test_api_humo_terminal'),

        ];

        return $config;
    }

    public static function getCardPhone($request)
    {
        $config = self::buildAuthorizeConfig();
        $card_type = CardHelper::checkTypeCard($request->card)['type'];
        $splitCardInfo = CardHelper::getAdditionalCardInfo($request->card, $request->exp);
        //Prepare request input
        $input_params = ['card_number' => $splitCardInfo['card'], 'expire' => $splitCardInfo['exp']];
        $input_id = 'test_' . uniqid(rand(), 1);
        $input_method = 'card.register';
        $test_api_get_balance = false;
        if ($config['test_api_balance_switch']) {
            if (($card_type == 2) && env('HUMO_TO_UNIVERSAL_BALANCE_SWITCH')) {
                $test_api_get_balance = false;
            } elseif (($card_type == 1) && env('UZCARD_TO_UNIVERSAL_BALANCE_SWITCH')) {
                $test_api_get_balance = false;
            } else {
                $input_id = 'api_card_balance_' . time() . uniqid(rand(), 10);
                $test_api_get_balance = true;
            }
        }
        $input = json_encode([
            'jsonrpc' => '2.0',
            'id' => $input_id,
            'method' => $input_method,
            'params' => $input_params,
        ]);
        if ($test_api_get_balance) {
            $result = self::testApiGetBalance($splitCardInfo['card'], $splitCardInfo['exp']);
        } else {
            $result = self::backOffice($input, $request->url_humo);
        }
        Log::channel('cards')->info(print_r($input, 1));
        Log::channel('cards')->info(json_encode($result, 1));
        return $result;
    }

    public static function backOffice($input, $url_humo = false)
    {

        $config = self::buildAuthorizeConfig();

        if ($url_humo) {
            $curl = curl_init($config['url_humo']);
            $token = OldCrypt::decryptString($config['universal_token_uzcard']);
        } else {
            $curl = curl_init($config['url_uzcard']);
            $token = OldCrypt::decryptString($config['universal_token_uzcard']);
        }

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Unisoft-Authorization: Bearer ' . $token
            )
        );
        curl_setopt($curl, CURLOPT_POSTFIELDS, $input);

        try {
            $result = curl_exec($curl);
            $result = json_decode($result, JSON_UNESCAPED_UNICODE);  // проверить
        } catch (\Exception $e) {
            $result = [
                'status' => 'error'
            ];
            Log::info('universal error');
            Log::info($e);
        }

        return $result;
    }

    public static function testApiGetBalance($card_number, $expiry_date)
    {

        $config = self::buildAuthorizeConfig();
        //название параметров number, expiryDate отличаются поэтому создаем новый input
        $input = json_encode([
            'jsonrpc' => '2.0',
            'id' => 'api_card_balance_' . time() . uniqid(rand(), 10),
            'method' => 'card.register',
            'params' => [
                'number' => $card_number,
                'expiryDate' => $expiry_date,
            ],
        ]);

        $curl = curl_init($config['test_api_url_card_balance']);

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, $config['test_api_login'] . ':' . $config['test_api_password']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt(
            $curl,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json'
            )
        );

        curl_setopt($curl, CURLOPT_POSTFIELDS, $input);

        try {
            $result = curl_exec($curl);
            $result = json_decode($result, JSON_UNESCAPED_UNICODE);  // проверить
        } catch (\Exception $e) {
            $result = [
                'status' => 'error'
            ];
            Log::info('test api error');
            Log::info($e);
        }

        return $result;
    }
}
