<?php

namespace App\Helpers;

use App\Facades\OldCrypt;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class InsureHelper {

    public static function requestInsure($config, $data){
        Log::channel('insure')->info('Log insure.start');
        //TODO: Расскомментировать если будет переход на версию API v2
        /*$request = json_encode([
            "credit_number" => $data['credit_number'],
            "client_name" => $data['client_name'],
            "credit_amount" => $data['credit_amount'],
            "passport_serial" => $data['passport_serial'],
            "passport_number" => $data['passport_number'],
            "passport_date" => $data['passport_date'],
            "date_contract_begin" => $data['date_contract_begin'],
            "date_contract_end" => $data['date_contract_end']
        ]);*/
        $request = json_encode([
            "credit_number" => $data['credit_number'],
            "client_name" => $data['client_name'],
            "credit_amount" => $data['credit_amount'],
            "passport_serial" => $data['passport_serial'],
            "passport_number" => $data['passport_number'],
            "passport_date" => $data['passport_date'],
            "term" => $data['term'],
        ]);
        $result['response']['request'] = $request;

        Log::channel('insure')->info(print_r($request,1));
        $curl = curl_init($config['url']);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['x-api-key: '.OldCrypt::decryptString($config['token'])]);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $request);

        $response = curl_exec($curl);
        $arResponse = json_decode($response, JSON_UNESCAPED_UNICODE);
        $result['response']['response'] = $response;
        $result['data'] = $arResponse;
        if(isset($arResponse['Status']) && $arResponse['Status'] == 'Create')
            $result['status'] = 'success';
        else $result['status'] = 'error';

        Log::channel('insure')->info(print_r($result,1));
        Log::channel('insure')->info('Log insure.end');
        return $result;
    }
}
