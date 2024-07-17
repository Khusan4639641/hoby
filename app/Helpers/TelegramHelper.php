<?php


namespace App\Helpers;


use App\Models\KycInfo;


class TelegramHelper {

    const BOT_TOKEN = '1892876527:AAFULO5DCPTtMMM1Ed0tJ60cdBfLLHLNmk4';

    public static function send($msg){

        if($kycinfo = KycInfo::where('status',1)->get()) {

            foreach ($kycinfo as $kyc) {

                $url = "https://api.telegram.org/bot" . self::BOT_TOKEN . "/sendMessage?chat_id=" . $kyc->chat_id;
                $url = $url . "&parse_mode=html&text=" . urlencode($msg);
                $ch = curl_init();
                $optArray = array(
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true
                );
                curl_setopt_array($ch, $optArray);
                $result = curl_exec($ch);
                curl_close($ch);

            }

            return true;

        }

    }

    public static function sendByChatId($chat_id,$msg){

    
		$url = "https://api.telegram.org/bot" . self::BOT_TOKEN . "/sendMessage?chat_id=" . $chat_id;
		$url = $url . "&parse_mode=html&text=" . urlencode($msg);
		$ch = curl_init();
		$optArray = array(
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => true
		);
		curl_setopt_array($ch, $optArray);
		$result = curl_exec($ch);
		curl_close($ch);

        return true;

    }





}
