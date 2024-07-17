<?php
namespace App\Services;

use App\Helpers\CardHelper;
use App\Helpers\EncryptHelper;
use App\Models\Card;
use Illuminate\Support\Facades\Log;


class AddCardInfoHumoUzcard
{
    static public  $results = [];

    static public function getHumoCards(string $phone, int $buyer_id)
    {
        $result = [];
        $resultFromApi = self::requestToApi($phone);
        Log::channel('humo_api')->info("Response to Humo api $phone\n".json_encode($resultFromApi, 1));
        if(count($resultFromApi)>0) {
            foreach ($resultFromApi as $cardDatas) {
                if(CardHelper::checkTypeCard($cardDatas->pan)['name']=='HUMO'){
//              check card with guid
                $guid = md5($cardDatas->pan);
                $checkCard = Card::where('guid',$guid)->count();
//                if not exist card // если такая карта уже есть, не будем добавлять
                if($checkCard==0) {
                    //add new card
                    $user_cards = new Card();
                    $user_cards->user_id = $buyer_id;
                    $user_cards->card_name = $cardDatas->fullName==null ? "":$cardDatas->fullName;
                    $user_cards->card_number = EncryptHelper::encryptData($cardDatas->pan);
                    $user_cards->card_valid_date = EncryptHelper::encryptData($cardDatas->expiry);
                    $user_cards->phone = str_replace('+','',$phone);
                    $user_cards->type = EncryptHelper::encryptData('HUMO');
                    $user_cards->guid = $guid;
                    $user_cards->status = 0;
                    $user_cards->hidden = 0;
                    $user_cards->card_number_prefix = substr($cardDatas->pan, 0, 8);
                if ($user_cards->save()) {
                    $result[][$phone] = true;
                } else {
                            $result[][$phone] = false;
                        }
                    }
                }
            }
        }
        return $result;
        }
    static public function requestToApi($phone) {

        $url = config('test.humo_info_url');
        $token = config('test.humo_info_key');

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $headers = array(
            "Accept: application/json",
            "Authorization: Basic $token",
            "Content-Type: application/json",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode(['data' => $phone]));
        Log::channel('humo_api')->info("Request to Humo api $phone\n".print_r(json_encode(['data' => $phone]), 1));
//for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        $resp = curl_exec($curl);
        curl_close($curl);
        return json_decode($resp);
    }
}
