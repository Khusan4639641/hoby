<?php


namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class CurlHelper
{

    public static function send($options, $returnArray=true){


        Log::channel('curl')->info('curl request before:');
        Log::channel('curl')->info($options);

        $errors = [];
        if(empty($options['url'])) $errors[] = 'Url not fill';
        if(empty($options['header'])) $options['header'] = ['Content-Type:application/json'];
        if(empty($options['method'])) $options['method'] = 'GET';
        $options['method'] = strtoupper( $options['method']);

        Log::channel('curl')->info('curl request after:');
        Log::channel('curl')->info($options);


        if( count($errors)==0 ) {

            try {
                if ($curl = curl_init()) {
                    curl_setopt($curl, CURLOPT_URL, $options['url']);
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

            		//curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,12);

                    if (isset($options['basic'])) {
                        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                        curl_setopt($curl, CURLOPT_USERPWD, $options['login'] . ':' . $options['password']);
                    }

                    if ($options['method'] == 'POST') curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); //for solving certificate issue
                    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
                    curl_setopt($curl, CURLOPT_HTTPHEADER, $options['header']);
                    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);

                    if (!empty($options['data'])) curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($options['data'], JSON_UNESCAPED_UNICODE));

                    $result = curl_exec($curl);
                    curl_close($curl);

					Log::channel('curl')->info('request 1:');
                    Log::channel('curl')->info($options);

                    Log::channel('curl')->info('response 1:');
                    Log::channel('curl')->info($result);

                    if($result=='' || mb_strpos('!DOCTYPE',$result)>0){
                        return ['status'=>'success','data'=>500];
                    }

                    if ($returnArray) $result = json_decode($result, true);

                    return ['status' => 'success', 'data' => $result];
                }

            }catch (\Exception $e){
                return ['status'=>'error','data'=>$e];
            }

        }

        return ['status'=>'error','data'=>$errors];



    }


    public static function requestCurl($options){

        $curl = curl_init($options['url']);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', $options['header']));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $options['data']);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

        try {
            $result = curl_exec($curl);
            $result = json_decode($result, JSON_UNESCAPED_UNICODE);

        } catch (\Exception $e) {
            $result = [
                'status' => 'error not sended!'
            ];

            Log::channel('cronpayment')->info('error curl ');
            Log::info($e);
        }


        return $result;



    }
}
