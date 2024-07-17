<?php

namespace App\Services;

use App\Facades\OldCrypt;
use App\Helpers\CardHelper;
use Illuminate\Support\Facades\Log;

class ScoringService
{
    public static function Universal($input)
    {

        $url_humo = CardHelper::checkTypeCard(json_decode($input)->params->card_number)['type'] == 2;

        if ($url_humo) {
            $curl = curl_init(config('test.universal_url_humo'));
            $token = OldCrypt::decryptString(config('test.universal_token_humo'));
//            $token = OldCrypt::decryptString($config['universal_token_uzcard']); // так же как в CardContoller
        } else {
            $curl = curl_init(config('test.universal_url_uzcard'));
            $token = OldCrypt::decryptString(config('test.universal_token_uzcard'));
        }

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Unisoft-Authorization: Bearer ' . $token)
        );
        curl_setopt($curl, CURLOPT_POSTFIELDS, $input);
        $result = curl_exec($curl);
        $result = json_decode($result, JSON_UNESCAPED_UNICODE);  // проверить

        if ($url_humo) {
            if (isset($result['result'])) {
                $newResult = [];
                foreach ($result['result'] as $key => $item) {
                    if (!is_array($item)) {
                        $newResult[$key] = $item;
                    }
                }
                $result['result'] = $newResult;
            }
        }

        return $result;

    }

    public static function test($input)
    {
        $encodedInput = \GuzzleHttp\json_decode($input);

        $curl = curl_init(config('test.test_api_url_card_scoring'));

        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_USERPWD, config('test.test_api_login') . ':' . config('test.test_api_password'));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json')
        );

        curl_setopt($curl, CURLOPT_POSTFIELDS, $input);

        $result = curl_exec($curl);
        $result = json_decode($result, JSON_UNESCAPED_UNICODE);

        if (isset($result['result'])) {

            $newResult = [];

            foreach ($result['result'] as $item) {

                if (strtotime($item['date']) >= strtotime(date('M-Y', strtotime($encodedInput->params->start_date))) && strtotime($item['date']) <= strtotime(date('M-Y', strtotime($encodedInput->params->end_date)))) {
                    $newResult[$item['date']] = $item['salaries']['amount'] + $item['p2pCredit']['amount'];
                }

            }
            $result['result'] = $newResult;
        }
        $result['status'] = true;

        return $result;

    }

}
