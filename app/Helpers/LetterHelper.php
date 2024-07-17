<?php


namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LetterHelper
{
    private static $auth_url = 'https://hybrid.pochta.uz/token';
    private static $url      = 'https://hybrid.pochta.uz/api/PdfMail';

    // Отправка письма

    /**
     * @throws \JsonException
     */
    public static function createLetter($receiver, $address, $region, $area, $pdf_stream) {

        Log::channel('letters')->info("createLetter");
        Log::channel('letters')->info(json_encode([
            'receiver' => $receiver,
            'address'  => $address,
            'region'   => $region,
            'area'     => $area,
            //'document64' => $pdf_stream
        ], JSON_THROW_ON_ERROR));

        $bearer_token = self::getBearerToken();
        if (!$bearer_token) {
            Log::channel('letters')->error('Error! BearerToken does not exist!');
            return ['status' => 'error', 'info' => 'Authorization failed'];
        }
        Log::channel('letters')->info('Bearer token: ' . $bearer_token);

        $response = self::sendCreateLetterRequest($bearer_token, $receiver, $address, $region, $area, $pdf_stream);

        Log::channel('letters')->info('Response when sendCreateLetterRequest():');
        Log::channel('letters')->info($response);

        $auth_err_msg = "Authorization has been denied for this request.";
        if ( isset($response["Message"]) && ($response["Message"] === $auth_err_msg) ) {
            Log::channel('letters')->error('Invalid BearerToken! Dropping old one and Requesting a new one...');
            Setting::where("param", "pochtauz_api_token")->first()->update(['value' => null]);
            Setting::where("param", "pochtauz_api_token_expires_at")->first()->update(['value' => null]);
            $bearer_token = self::getBearerToken();  // Получить новый токен
            // Отправить запрос на 'Создание письма' заново с новым токеном
            $response = self::sendCreateLetterRequest($bearer_token, $receiver, $address, $region, $area, $pdf_stream);
            Log::channel('letters')->info('Response when sendCreateLetterRequest() for the second time:');
            Log::channel('letters')->info($response);
        }

        if (isset($response['Id'])) {

            return ['status' => 'success', 'data' => $response];

        }

        return ['status' => 'error', 'info' => 'Failed to retrieve data'];
    }

    public static function getBearerToken() {
        Log::channel('letters')->info('Getting BearerToken...');

        /* 1. Получить токен и expires_at из базы */
        $existing_token = Setting::getParam('pochtauz_api_token');
        $existing_token_expires_at = Setting::getParam('pochtauz_api_token_expires_at');

        /* 2. Если не найдено, то запросить новый токен, сохранить в базе и вернуть из метода */
        if (!$existing_token || !$existing_token_expires_at) {

            Log::channel('letters')
                ->info('Token not found in `settings` table, requesting new BearerToken...')
            ;
            $new_token = self::requestAndSaveNewToken();
            Log::channel('letters')->info('Got new BearerToken.');

            return $new_token;

        }

        /* 3. Если найдено, то проверить expires_at */
        // $is_token_expired = '1651645601' >= $existing_token_expires_at;
        $is_token_expired = strtotime(Carbon::now()) >= $existing_token_expires_at;

        /* Если дата истекла, то запросить новый токен, сохранить в базе и вернуть */
        if ($is_token_expired) {

            Log::channel('letters')
                ->info('Token is expired, requesting new BearerToken...')
            ;
            $new_token = self::requestAndSaveNewToken();
            Log::channel('letters')->info('Got new BearerToken.');

            return $new_token;

        }

        /* Если дата актуальна, то вернуть токен */
        Log::channel('letters')->info('BearerToken is present and everything is Ok.');
        return $existing_token;
    }

    private static function requestAndSaveNewToken() {
        Log::channel('letters')->info('Requesting new BearerToken...');

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$auth_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_POSTFIELDS => 'grant_type=password&username='.config('test.uzbpochta_username').'&password='.config('test.uzbpochta_password').'',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $response = json_decode($response, true);

        if ( !is_array($response) ) {
            Log::channel("letters")->info("Response is not Array or Couldn't convert Response into Array!");
            throw new HttpResponseException(
                response()->json([
                    "status"   => "error",
                    "response" => [
                        "code"   => 500,
                        "message" => [
                            (object) [
                                "type" => "danger",
                                "text" => __('panel/letters.err_did_not_get_norm_response')
                            ]
                        ],
                        "errors" => [],
                    ],
                    "data"     => [$response]
                ], 500)
            );
        }

        Log::channel("letters")
            ->info("Response from Pochta when requestAndSaveNewToken():\n"
                . implode($response)
            )
        ;

        if ( !isset($response['access_token'], $response['expires_in']) ) {
            Log::channel("letters")->info("Response doesn't contain 'access_token' or 'expires_in' fields!");
            throw new HttpResponseException(
                response()->json([
                    "status"   => "error",
                    "response" => [
                        "code"   => 500,
                        "message" => [
                            (object) [
                                "type" => "danger",
                                "text" => __('panel/letters.err_did_not_get_token')
                            ]
                        ],
                        "errors" => [],
                    ],
                    "data"     => [$response]
                ], 500)
            );
        }

        $expires_at = strtotime(Carbon::now()->addSeconds($response['expires_in']-60*60));

        Setting::setParam('pochtauz_api_token', $response['access_token']);
        Setting::setParam('pochtauz_api_token_expires_at', $expires_at);

        return $response['access_token'];
    }

    private static function sendCreateLetterRequest($bearer_token, $receiver, $address, $region, $area, $pdf_stream, $asArray=true) {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
              "Receiver": "'.$receiver.'",
              "Address": "'.$address.'",
              "Region": '.$region.',
              "Area": '.$area.',
              "Document64": "'.$pdf_stream.'"
            }',
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer $bearer_token",
                "Content-Type: application/json"
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        return $asArray ? json_decode($response, true) : $response;
    }
}
