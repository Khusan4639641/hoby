<?php

namespace App\Helpers;

use App\Helpers\V3\OTPAttemptsHelper;
use App\Models\Buyer;
use App\Models\ContractPaymentsSchedule;
use Illuminate\Support\Facades\Log;

class SmsHelper {

    const SMS_SEND_SUCCESS = 'Request is received';

    public static function generateCode(){
        return OTPAttemptsHelper::generateCode(6);
    }


    /**
     * @param string $phone
     * @param string $text
     * @return array
     * @throws \JsonException
     */
    public static function sendSms($phone, $text){
        Log::info("Sms code: " . $phone . ': '.$text);

        // if(config('app.debug'))       return file_put_contents("sms_code.log", $phone.': '.$text);

        $phone = correct_phone($phone);

        // для компаний префикс
        if( mb_strlen($phone)==9 ) $phone = '998' . $phone;

        $sms = [
            'messages' => [
                [
                    "recipient" => $phone,
                    "message-id" => 'test_' . time(), //"dos".time(),
                    'sms' => [
                        "originator" => "resusNasiya",
                        'content' => [
                            'text' => $text
                        ],

                ],                                                                                                                                                                                                                                       ],
            ]
        ];

        $data_string = json_encode($sms, JSON_THROW_ON_ERROR);

        $ch = curl_init(env('SMS_CODE_SERVICE_URL', 'http://91.204.239.44/broker-api/send'));
        curl_setopt($ch, CURLOPT_USERPWD, config('test.sms_login').':' . config('test.sms_password'));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data_string))
        );



        $result = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code !== 200 || $result !== SmsHelper::SMS_SEND_SUCCESS) {
            Log::channel('play_mobile_errors')->info("Response: " . json_encode($result) . " Code: $http_code Phone: $phone");
        }
        return [$result, $http_code];
    }

    // при верификации КАТМА
   /* public static function sendTextSmsVerified($user){
        // отправить смс о том, что клиент прошел верификацию
        $txt = 'Pozdravlayem! Vi proshli verifikatsiyu na platforme test. Vash limit '. @$user->settings->balance .' sum. Dlya osushestvleniya pokupok ispolzuyte svoy nomer telefona.
                    / Tabriklaymiz! Siz verifikatsiya bosqichidan otdingiz. Sining limit '. @$user->settings->balance .' sum. Xaridlarni amalga oshirishda telefon raqamingizni ishlating. test!';
        $result = self::sendSms($user->phone, $txt);
        return $result;
    }

    // не пройдена верификация КАТМА
    public static function sendTextSmsnot-verified($phone){

        // отправить смс о том, что клиент НЕ прошел верификацию
        $txt = "Uvajaemiy polzovatel, k sojaleniyu vi ne proshli protsess verifikatsii. Mi vam soobshim otdelnim soobsheniyem kogda mojno povtorit eshe raz. test!
                        / Hurmatli foydalanuvchi, afsuski siz verifikatsiya bosqichidan ota olmadingiz. Qayta urinib korishingiz uchun alohida SMS xabar beramiz. test!";

        $result = self::sendSms($phone, $txt);
        return $result;

    } */

    /**
     * @param $request
     * @return bool
     *  для текущего крона по месяцам
     */

    public static function sendSmsPayment($request){

        //отправить смс по списаниям
        $buyer = Buyer::find($request->buyer_id);
        $debt = ContractPaymentsSchedule::find($request->schedule_id);

        if ($request->lc) {
            $txt = "Hurmatli mijoz, sizning shaxsiy hisobingizdan shartnoma N" . $request->contract_id
                . " bo'yicha " . number_format($request->amount, 2, '.', '')
                . " so'm yechib olindi. Joriy oy qarzdorlik qoldig'i " . number_format($debt->balance - $request->amount, 2, '.', '')
                . " so'm. Tel: " . callCenterNumber(2); // (78) 777 15 15
        }
        else {
            $pan = '**** ' . substr($request['info_card']['public_number'], -4);

            $txt = "Hurmatli mijoz, sizning kartangizdan shartnoma N" . $request->contract_id
                 . " bo'yicha " . number_format($request->sum, 2, '.', '')
                 . " so'm yechib olindi. Joriy oy qarzdorlik qoldig'i " . number_format($request->debt, 2, '.', '')
                 . " so'm. Tel: " . callCenterNumber(2); // (78) 777 15 15
        }


        if ($buyer) {
            [$send, $http_code] = SmsHelper::sendSms($buyer->phone, $txt);
            Log::channel('payment')->info('spisali ' . $request->amount);
            Log::channel('payment')->info("Sms code: " . $buyer->phone . ': '.$txt);
            Log::channel('payment')->info(print_r($send, 1));
        }

        return true;
    }

    /**
     * @param $request
     * @return bool
     * для нового крона по договорам - 1 списание, 1 смс
     */


    public static function sendSmsCronPayment($request){

        //отправить смс по списаниям
        $buyer = Buyer::find($request->buyer_id);

        if ($request->lc) {
            $txt = "Hurmatli mijoz, sizning shaxsiy hisobingizdan shartnoma N" . $request->contract_id
                 . " bo'yicha " . number_format($request->amount, 2, '.', '')
                 . " so'm yechib olindi. Qarzdorlik qoldig'i " . number_format($request->debt, 2, '.', '')
                 . " so'm. Tel: " . callCenterNumber(2); // (78) 777 15 15
        }
        else {
            $txt = "Hurmatli mijoz, sizning kartangizdan shartnoma N" . $request->contract_id
                 . " bo'yicha " . number_format($request->amount, 2, '.', '')
                 . " so'm yechib olindi. Qarzdorlik qoldig'i " . number_format($request->debt, 2, '.', '')
                 . " so'm. Tel: " . callCenterNumber(2); // (78) 777 15 15
        }


        if($buyer){
            $send = SmsHelper::sendSms($buyer->phone, $txt);
            Log::channel('cron_payments')->info('spisali ' . $request->amount . ' sum po dogovoru №'  . $request->contract_id);
            Log::channel('cron_payments')->info("Sms code: " . $buyer->phone . ': '.$txt);
            Log::channel('cron_payments')->info(print_r($send, 1));
        }

        return true;
    }


}
