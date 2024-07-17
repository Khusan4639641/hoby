<?php

namespace App\Services\API\V3;

use App\Models\Buyer;
use App\Models\Contract;
use App\Models\Notifications;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Validator;
use Log;
use Str;

class FcmService extends BaseService
{
    public static function send($firebaseToken, string $title, string $body, array $data, $flash = true): bool
    {
        try {
            // $firebaseToken = User::whereNotNull('device_token')->pluck('device_token')->all();
            $SERVER_API_KEY = config('test.firebase.server_key');
            $data = [
                "registration_ids" => [$firebaseToken],
                "notification" => [
                    "title" => $title,
                    "body" => $body,
                ],
                'data' => [
                    "type" => $data['type'] ?? null,
                    "element_id" => $data['element_id'] ?? null
                ]
            ];
            $dataString = json_encode($data);
            $headers = [
                'Authorization: key=' . $SERVER_API_KEY,
                'Content-Type: application/json',
            ];
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);
            $response = curl_exec($ch);
            $result = json_decode($response, true);
            if (isset($result['success']) && $result['success'] > 0) {
                return $flash ? self::handleResponse($result) : true;
            }
            return false;
        } catch (\Throwable $th) {
            throw $th;
            return false;
        }
    }

    public static function validateUpdateToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'system' => ['required', Rule::in('ios', 'android', 'web')],
            'fcm_token' => 'required|string'
        ]);
        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function updateToken(Request $request)
    {
        $inputs = self::validateUpdateToken($request);
        $user = Auth::user();
        if ($inputs['system'] == 'ios') {
            $user->firebase_token_ios = $inputs['fcm_token'];
        }
        if ($inputs['system'] == 'android') {
            $user->firebase_token_android = $inputs['fcm_token'];
        }
        $user->device_os = $inputs['system'];
        $user->save();
        return self::handleResponse();
    }

    public static function getMessages($message_key)
    {
        $messages = [
            'pay_in_10_days' => [
                'ru' => [
                    'title' => "ПОГАШЕНИЕ ПО КОНТРАКТУ",
                    'body' => "Уважаемый клиент, напоминаем Вам о предстоящей оплате в размере {sum} сум в {date} по договору {contract_number}."
                ],
                'uz' => [
                    'title' => "SHARTNOMA BO'YICHA TO'LOV",
                    'body' => "Hurmatli mijoz, Sizga {contract_number}  shartnoma bo'yicha {sum} so'mni {date} kuni to'lovni amalga oshirishingiz kerakligini eslatib o'tamiz."
                ]
            ],
            'pay_tomorrow' => [
                'uz' => [
                    'title' => "ПОГАШЕНИЕ ПО КОНТРАКТУ",
                    'body' => "Уважаемый клиент, напоминаем что завтра день оплаты рассрочки по контракту {contract_number} в размере {sum} суммы. Просим Вас пополнить карту на нужную сумму."
                ],
                'ru' => [
                    'title' => "SHARTNOMA BO'YICHA TO'LOV",
                    'body' => "Hurmatli mijoz,  ertaga {contract_number} shartnoma bo'yicha  {sum} so'm miqdoridagi  to'lovni amalga oshirishingiz kerak bo'lgan kun. Kartani kerakli miqdorda to'ldirishingizni so'raymiz."
                ]
            ],
            'pay_expiry_10' => [
                'uz' => [
                    'title' => "ПОГАШЕНИЕ ПО КОНТРАКТУ",
                    'body' => "Уважаемый клиент, у Вас имеется просрочка на 10 дней по договору {contract_number}  . Просим внести оплату в размере {sum} сум."
                ],
                'ru' => [
                    'title' => "SHARTNOMA BO'YICHA TO'LOV",
                    'body' => "Hurmatli mijoz, Siz {contract_number} shartnoma bo'yicha oylik to'lovni 10 kunga kechiktirdingiz . Sizdan {sum} miqdorida to'lovni amalga oshirishingizni so'raymiz."
                ]
            ],
            'pay_expiry_15' => [
                'uz' => [
                    'title' => "ПОГАШЕНИЕ ПО КОНТРАКТУ",
                    'body' => "Уважаемый клиент, у Вас имеется просрочка на 15 дней по договору {contract_number}  . Просим закрыть долг."
                ],
                'ru' => [
                    'title' => "SHARTNOMA BO'YICHA TO'LOV",
                    'body' => "Hurmatli mijoz, Siz {contract_number} shartnoma bo'yicha oylik to'lovni 15 kunga kechiktirdingiz . Qarzngiz uchun to'lovni amalga oshirishingizni so'raymiz."
                ]
            ],
            'contract_complete' => [
                'uz' => [
                    'title' => "SHARTNOMA YOPILDI",
                    'body' => "Hurmatli mijoz, Sizning {contract_number} raqamli shartnomangiz yopildi."
                ],
                'ru' => [
                    'title' => "ДОГОВОР ЗАКРЫТ",
                    'body' => "Уважаемый клиент, Ваш договор по номеру {contract_number} был закрыт."
                ]
            ],
            'contract_rejected' => [
                'uz' => [
                    'title' => "SHARTNOMA BEKOR QILINDI",
                    'body' => "Hurmatli mijoz, Sizning {contract_number} raqamli shartnomangiz bekor qilindi."
                ],
                'ru' => [
                    'title' => "ДОГОВОР ОТМЕНЁН",
                    'body' => "Уважаемый клиент, Ваш договор по номеру {contract_number} был отменен."
                ]
            ],
        ];

        return $messages[$message_key] ?? false;
    }

    public static function notifyIfContractComplete($contract_id)
    {
        Log::info(str_repeat('*',30).'FCMSERVICE'.str_repeat('*',30));
        Log::info("FcmService::notifyIfContractComplete($contract_id);");
        try {
            $contract_id = (int)$contract_id;
            $contract = Contract::withCount('entries')->find($contract_id);
            if(!$contract){
                Log::info("FcmService - contract not found");
                return false;
            }
            $user = Buyer::find($contract->user_id);
            if(!$user){
                Log::info("FcmService - buyer not found");
                return false;
            }
            Log::info("FcmService - contract STATUS: ". $contract->status);
            if($contract->status == Contract::STATUS_COMPLETED){
                $firebase_token = $user->device_os == 'android' ? $user->firebase_token_android : $user->firebase_token_ios;
                Log::info("FcmService - device_os: ". $user->device_os);
                Log::info("FcmService - firebase_token: ". $firebase_token);
                if($firebase_token){
                    $message = self::getMessages('contract_complete');
                    $lang = $contract->user->lang ?? 'uz';
                    $title = $message[$lang]['title'];
                    $body = str_replace('{contract_number}',$contract->id,$message[$lang]['body']);
                    //Inser to DB
                    $notification_data = [
                        'id' => Str::uuid(),
                        'type' => self::class,
                        'notifiable_type' => User::class,
                        'notifiable_id' => $contract->user_id,
                        'data' => json_encode([
                            'type' => 'fcm',
                            'time' => date('H:i:s Y-m-d'),
                            'title_ru' => str_replace('{contract_number}',$contract->id,$message['ru']['title']),
                            'title_uz' => str_replace('{contract_number}',$contract->id,$message['uz']['title']),
                            'message_uz' => str_replace('{contract_number}',$contract->id,$message['uz']['body']),
                            'message_ru' => str_replace('{contract_number}',$contract->id,$message['ru']['body']),
                        ])
                    ];
                    Notifications::create($notification_data);
                    self::send($firebase_token,$title,$body,false);
                    Log::info("FcmService - NOTIFICIFATION SEND");
                    return true;
                }
            }
            return false;
        } catch (\Throwable $th) {
            Log::info("FcmService - error". $th);
            return false;
        }
    }

    public static function notifyIfContractRejected($contract_id)
    {
        Log::info(str_repeat('*',30).'FCMSERVICE'.str_repeat('*',30));
        Log::info("FcmService::notifyIfContractRejected($contract_id);");
        try {
            $contract_id = (int)$contract_id;
            $contract = Contract::find($contract_id);
            if(!$contract){
                Log::info("FcmService - contract not found");
                return false;
            }
            $user = Buyer::find($contract->user_id);
            if(!$user){
                Log::info("FcmService - buyer not found");
                return false;
            }
            Log::info("FcmService - contract STATUS: ". $contract->status);
            if($contract->status == Contract::STATUS_CANCELED){
                $firebase_token = $user->device_os == 'android' ? $user->firebase_token_android : $user->firebase_token_ios;
                Log::info("FcmService - device_os: ". $user->device_os);
                Log::info("FcmService - firebase_token: ". $firebase_token);
                if($firebase_token){
                    $message = self::getMessages('contract_rejected');
                    $lang = $contract->user->lang ?? 'uz';
                    $title = $message[$lang]['title'];
                    $body = str_replace('{contract_number}',$contract->id,$message[$lang]['body']);
                    //Inser to DB
                    $notification_data = [
                        'id' => Str::uuid(),
                        'type' => self::class,
                        'notifiable_type' => User::class,
                        'notifiable_id' => $contract->user_id,
                        'data' => json_encode([
                            'type' => 'fcm',
                            'time' => date('H:i:s Y-m-d'),
                            'title_ru' => str_replace('{contract_number}',$contract->id,$message['ru']['title']),
                            'title_uz' => str_replace('{contract_number}',$contract->id,$message['uz']['title']),
                            'message_uz' => str_replace('{contract_number}',$contract->id,$message['uz']['body']),
                            'message_ru' => str_replace('{contract_number}',$contract->id,$message['ru']['body']),
                        ])
                    ];
                    Notifications::create($notification_data);
                    self::send($firebase_token,$title,$body,false);
                    Log::info("FcmService - NOTIFICIFATION SEND");
                    return true;
                }
            }
            return false;
        } catch (\Throwable $th) {
            Log::info("FcmService - error". $th);
            return false;
        }
    }
}
