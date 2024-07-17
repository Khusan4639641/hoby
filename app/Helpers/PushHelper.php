<?php
/** 15.07.2021 */

namespace App\Helpers;


use Illuminate\Support\Facades\Log;

class PushHelper
{


    const TOKEN = 'key=AAAAj5G5tLo:APA91bEE8hJrGOX38feldVoZYPgGZn-QhAR-KyPNQ7ARYWuhZBWBbW-4owKPEbWDP1JZ85Be-Lq9eKRAh4ukiL-pH5xVoAm0BCh-nEk1n48r63zz0VWUjkb_DVowDg1DMb9cWsweRViK';
    const URL = 'https://fcm.googleapis.com/fcm/send';

    const TYPE_NEWS_ALL = 0;
    const TYPE_NEWS = 1;
    const TYPE_CONTRACT = 2;
    const TYPE_LEVEL = 3;
    const TYPE_PAYMENT = 4;


    /** отправка PUSH уведомлений
     * $options['type'] = news_all, news, contract, notify
     * $options['route'] = '/NewsPageRoute | /ContractPageRoute  - ссылка
     * $options['id'] = \d+         id новости, контракта и тд
     * $options['title'] = \w+      заголовок ru
     * $options['text}'] = \w+       описание ru
     * $options['user_id'] = \d+    id клиента
     * $options['system'] = \w+    os устр-ва клиента
     * */

    public static function send($options){
        Log::channel('push')->info($options);
        $errors = [];
        if(!isset($options['type']) ) {
            Log::channel('push')->info('ERROR type not set');

            return [
                'status'=>'error',
                'info'=> 'type not set!'
            ];
        }
        // для всех
        if( in_array($options['type'] , [1,2,3,4] ) ) {
            if(!isset($options['id'])) $errors[] = 'id not set!';
        }

        if(!isset($options['title'])) $errors[] = 'title not set!';

        if(!isset($options['text'])) $errors[] = 'text not set!';

        if(count($errors)) {
            Log::channel('push')->info($errors);
            return [
                'status'=>'error',
                'errors'=>$errors
            ];
        }

        if((int)$options['type']!=PushHelper::TYPE_NEWS_ALL){
            $route_local = $options['buyer']['token'] ?? null;
        }


        if($options['buyer']['status']!='success'){
            Log::channel('push')->info('ERROR buyer not set');
            return [
                'status'=>'error',
                'info'=> 'Buyer not set!'
            ];
        }

        //}

        $route = [
            0 => '/NewsPageRoute',
            1 => '/NewsUserPageRoute',
            2 => '/ContractPageRoute',
            3 => '/LevelPageRoute',
            4 => '/HistoryPageRoute',
        ];


        $send =0;
        $systems = ['android','ios'];
        foreach ($systems as $system) {
            $send++;
            if( ( $options['buyer']['system']=='android' && $options['type']!=PushHelper::TYPE_NEWS_ALL) || ($system=='android' && $options['type']==PushHelper::TYPE_NEWS_ALL) ){

                $options['data']['data'] = [
                    "screen" => $route[$options['type']] ?? '',
                    "sound" => "default",
                    "title" => $options['title'],
                    "body"  => $options['text'],
                    "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                    "id" => $options['id'] ?? ''
                ];

                if($options['type']==PushHelper::TYPE_NEWS_ALL) {
                    $route_local = '/topics/testNewsAndroid';
                }

            }elseif( ($options['buyer']['system']=='ios' && $options['type']!=PushHelper::TYPE_NEWS_ALL) || ($system=='ios' && $options['type']==PushHelper::TYPE_NEWS_ALL) ){

                $options['data']['notification'] = [
                    "title" => $options['title'],
                    "body"  => $options['text'],
                    "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                    "body_loc_key" => $route[$options['type']] ?? '',
                    "sound" => "default",
                    "title_loc_key" => $options['id'] ?? ''
                ];

                if($options['type']==PushHelper::TYPE_NEWS_ALL) {
                    $route_local = '/topics/testNewsIos';
                }

            }

            $options['header'] = [
                'Content-Type:application/json',
                'Authorization:' .  self::TOKEN // $options['buyer']['token'] // self::TOKEN
            ];

            $options['url'] = self::URL;

            $options['method'] = 'POST';

            $options['data']['to'] = $route_local;

            Log::channel('push')->info($options);

            $result = CurlHelper::send($options,true);

            Log::channel('push')->info('push result');
            Log::channel('push')->info($result);
            if($send>1 && $options['type']==PushHelper::TYPE_NEWS_ALL){
                break;
            }elseif($options['type']!=PushHelper::TYPE_NEWS_ALL){
                break;
            }

        }

        return  $result;

    }

    public static function sendTest($options){

        Log::channel('push')->info('test');
        Log::channel('push')->info($options);


    }


}
