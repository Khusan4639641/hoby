<?php

namespace App\Services\API\V3;

use App\Helpers\SmsHelper;
use App\Models\Card;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class BaseService
{
    public static function handleError($messages = [],  $status = 'error', $status_code = 400, $data = [])
    {
        throw new HttpResponseException(
            response()->json([
                'status' => $status,
                'error' => self::beautifyMessage($messages),
                'data' => $data,
            ], $status_code)
        );
    }

    public static function errorJson($messages = [],  $status = 'error', $status_code = 400, $data = []): JsonResponse
    {
        return new JsonResponse([
                'status' => $status,
                'error' => self::beautifyMessage($messages),
                'data' => $data,
            ], $status_code);
    }

    public static function handleResponse($data = [],  $status = 'success', $status_code = 200)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => $status,
                'error' => [],
                'data' => $data,
            ], $status_code)
        );
    }

    public static function successJson($data = [], $status = 'success', $status_code = 200): JsonResponse
    {
        return new JsonResponse([
            'status' => $status,
            'error' => [],
            'data' => $data,
        ], $status_code);
    }

    public static function beautifyMessage($messages = [])
    {
        $errors = [];
        if (!empty($messages)) {
            foreach ($messages as $key => $message) {
                if (is_array($message)) {
                    self::beautifyMessage($message);
                }
                $errors[] = [
                    'type' => 'danger',
                    'text' => is_array($message) ? $message[0] : $message
                ];
            }
        }
        return $errors;
    }

    public static function isCorrectPhone($phone)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return preg_match('/(998)[0-9]{9}/', $phone);
    }

    public static function changeUserStatus(&$user,$status)
    {

        $msg = 'OCR buyer change user status ' . $user->id .' from: ' . $user->status . ' to: ' . $status;
        Log::info($msg);
        if($status == 2){
            if(!isset($user->settings)){
                $status = 1;
            }
            // ВРЕМЕННО ОТПРАВИТЬ СМС DJ о том, что клиент ожидает ручную проверку
            $link = request()->getHttpHost() . '/ru/panel/buyers/' . $user->id;
            $msg = "У клиента id: <b>{$user->id}</b>\nИзменился статус на <b>ОЖИДАНИЕ ВЕРИФИКАЦИИ</b>!\n{$link}";
        }

         // временно , если нет карты
        if ( $status === 4 ) {
            if( $card = Card::where('user_id',$user->id)->first() ){
                $user->status = 4;
            }
            else {
                $msg = 'User без карты id: ' . $user->id . ' текущий status: ' . $user->status;
                Log::channel('users')->info($msg);
                $user->status = 1;
            }
        }
        else {
            // проверка на вип клиента - вип нельзя блочить, верифицируем
            if ( ( $user->vip === 1 ) && ( $status === 8 ) ) {
                $status = 4;
                // если клиент вип, отправим смс о верификации, вместо того, чтобы заблочить
                $msg = "Tabriklaymiz! Siz verifikatsiya bosqichidan o'tdingiz. Sizning limit " . @$user->settings->limit
                    . " sum. Xaridlarni amalga oshirishda telefon raqamingizdan foydalaning!"
                ;

                SmsHelper::sendSms($user->phone, $msg);
                Log::channel('katm')->info('vip : user_id: ' . $user->id . ' ' .   $msg);
            }
            $user->status = $status;
        }

        if($user->status==4){ // т.к. вверху временно, пока нет карты
            $msg = 'Верификация клиента <b>' . $user->id . "</b>\n" . 'Редактор: <b>' . Auth::user()->name . ' ' . Auth::user()->surname .'</b>';
            Log::channel('users')->info($msg);
            // если имеется ссылка от вендора, отправка смс клиенту для перехода по ней
            if(isset($user->personals) && $user->personals->vendor_link != ''){
                $limit = $user->settings->limit;
                $msg = "Siz resusNasiya platformasida ro'yxatdan o'tdingiz. Limitingiz {$limit} so'm. Hamkorlarimizdan xaridni davom ettiring " . $user->personals->vendor_link;
                SmsHelper::sendSms($user->phone,$msg);
                Log::channel('users')->info($msg);
            }
        }

        if($user->status==8){ // т.к. вверху временно, пока нет карты
            $msg = 'Блокировка клиента <b>' . $user->id . "</b>\n" . 'Редактор: <b>' . Auth::user()->name . ' ' . Auth::user()->surname .'</b>';
            Log::channel('users')->info($msg);
        }

        if($msg) Log::channel('users')->info($msg);
        $user->save();
    }

    public static function permissions($item, User $user)
    {
        $permissions = [];

        if ($user->can('detail', $item)) {
            $permissions[] = 'detail';
        }
        if ($user->can('modify', $item)) {
            $permissions[] = 'modify';
        }
        if ($user->can('delete', $item)) {
            $permissions[] = 'delete';
        }

        return $permissions;
    }
}
