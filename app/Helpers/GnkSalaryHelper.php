<?php


namespace App\Helpers;

use App\Models\BuyerGnkSalary;
use App\Models\BuyerPersonal;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request;

class GnkSalaryHelper
{
    private static $auth_url = 'https://prod.unimax.uz/auth/login';
    private static $url = 'https://prod.unimax.uz/api/v1/resource/salary';

    // Запрос по доходам от ГНК
    public static function getSalary($pinfl)
    {
        Log::channel('gnk_salary')->info("getSalary");

        if (!$pinfl) {
            $error = 'PINFL not set';
            Log::channel('gnk_salary')->info($error);
            return ['status' => 'error', 'info' => $error];
        }

        $bearer_token = self::getBearerToken();

        Log::channel('gnk_salary')->info($bearer_token);

        if (!$bearer_token) {
            return ['status' => 'error', 'info' => 'Authorization failed'];
        }

        $response = self::sendGetSalaryRequest($bearer_token, $pinfl);

        Log::channel('gnk_salary')->info('Response:');
        Log::channel('gnk_salary')->info($response);

        if (isset($response['data'])) {
//            $buyer_personal = BuyerPersonal::with('buyer')->where('pinfl_hash', md5($pinfl))->first();
//            $buyer = $buyer_personal->buyer;
//            $buyer->gnkSalary()->updateOrCreate(
//                ['user_id' => $buyer->id],
//                ['response' => json_encode($response)]
//            );
            return ['status' => 'success', 'data' => $response['data']];
        } else {
            return ['status' => 'error', 'info' => 'Failed to retrieve data'];
        }
    }

    private static function getBearerToken()
    {

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => self::$auth_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '{
                "username":"' . config('test.gnk_username') . '",
                "password":"' . config('test.gnk_password') . '"
            }
            ',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
            CURLOPT_HEADER => 1
        ));

        $response = curl_exec($curl);

        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header_string = substr($response, 0, $header_size);

        curl_close($curl);

        $headers = self::getResponseHeaderAsArray($header_string);

        return (is_array($headers) && isset($headers['Authorization'])) ? $headers['Authorization'] : null;
    }

    private static function sendGetSalaryRequest($bearer_token, $pinfl, $asArray = true)
    {

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
            CURLOPT_POSTFIELDS => '{
                "pin":"' . $pinfl . '"
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

    private static function getResponseHeaderAsArray($header_string)
    {

        $header_exploded = explode("\r\n", $header_string);

        $headers = [];

        foreach ($header_exploded as $item) {

            $item_exploded = explode(":", $item, 2);

            if (!isset($item_exploded[1])) {
                $item_exploded[1] = null;
            }

            $headers[trim($item_exploded[0])] = trim($item_exploded[1]);
        }

        return $headers;
    }
}
