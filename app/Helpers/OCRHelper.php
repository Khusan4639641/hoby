<?php

namespace App\Helpers;


// оптическое распознование образов
use App\Helpers\EncryptHelper;
use App\Models\BuyerAddress;
use App\Models\BuyerPersonal;
use Illuminate\Support\Facades\Log;

class OCRHelper{

    private static $url = 'http://185.183.243.73:5000/ocr';

    public static function send($data){

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
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json'
            ],
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        $data = json_decode($response,true);

        Log::info('OCR info:');
        Log::info($data);

        if(!empty($data['data'])){
            OCRHelper::correct($data['data']);
            return ['status'=>'success','data'=>$data['data']];
        }

        return  ['status'=>'error'];

    }

    // замена символов на числа
    public static function correct(&$data){

        $dic_num = ['o' => 0,'O'=>'0','s'=>5,'S'=>5, 'l'=>1,'L'=>1,'z'=>2,'Z'=>2,'b'=>6,'B'=>8];
        $dic_alpha = ['0' => 'O','1'=>'L','2'=>'Z','3'=>'B','5'=>'S','6'=>'B','8'=>'B','4'=>'.','7'=>'.','9'=>'.'];

        if(isset($data['number'])){
            $passport_serial = mb_substr($data['number'],0,2);
            $passport_number = mb_substr($data['number'],2,7);

            $data['number'] = strtr($passport_serial,$dic_alpha) . strtr($passport_number, $dic_num);
            //$data['number'] = preg_replace('/[a-zA-z\s]/iU','.',$data['number']);

        }

        if(isset($data['date_of_birth'])) {
            $data['date_of_birth'] = strtr($data['date_of_birth'], $dic_num);
            $data['date_of_birth'] = preg_replace('/[a-zA-z\s]/iU', '.', $data['date_of_birth']);
        }

        if(isset($data['personal_number'])) {
            $data['personal_number'] = strtr($data['personal_number'], $dic_num);
            $data['personal_number'] = preg_replace('/[a-zA-z\s]/iU', '.', $data['personal_number']);
        }


    }



}
