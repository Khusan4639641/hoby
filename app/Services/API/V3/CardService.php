<?php

namespace App\Services\API\V3;

use App\Helpers\CardHelper;
use App\Helpers\EncryptHelper;
use App\Helpers\SmsHelper;
use App\Helpers\UniversalHelper;
use App\Helpers\V3\OTPAttemptsHelper;
use App\Models\Buyer;
use App\Models\BuyerSetting;
use App\Models\Card;
use App\Models\CardScoring;
use App\Models\CardScoringLog;
use App\Models\Role;
use App\Models\User;
use App\Rules\CheckCardByPhone;
use App\Rules\CheckCardValidDate;
use App\Services\Mobile\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redis;

class CardService extends BaseService
{
    public static function validatedCardAdd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_number'     => ['required', 'numeric','digits:16'],
            'card_valid_date' => ['required', new CheckCardValidDate()]
        ]);
        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function validatedCardConfirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_token'     => 'required|string',
            'code'     => 'required|numeric',
            'card_number'     => ['required', 'numeric','digits:16'],
            'card_valid_date' => ['required', new CheckCardValidDate()]
        ]);
        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function cardAdd(Request $request)
    {
        $inputs = self::validatedCardAdd($request);
        $cardType = CardHelper::checkTypeCard($request->card_number)['type'];
        if ($cardType == 0) {
            return self::handleError([__('api.unknown_type_of_card')]);
        }
        $user = Auth::user();
        $request->merge(['card' => $inputs['card_number'], 'exp' => $inputs['card_valid_date']]);
        // $cardInfo = UniversalController::getCardPhone($request);
        $cardInfo = MainCardService::getCardPhone($request);
        Log::channel('cards')->info('new CardAdd for ' . $user->id);
        Log::channel('cards')->info($cardInfo);
        if (!isset($cardInfo['result']) || $cardInfo['status'] == false) {
            return self::handleError([__('api.unknown_type_of_card')]);
        }
        $cardInfo = $cardInfo['result'];
        $card_phone = $cardInfo['phone'] ?? false; //номер смс информирования
        if ($card_phone) {
            $cardNumber = $inputs['card_number'];
            $hashedCardNumber = md5($cardNumber);
            if ($card = Card::where('guid', $hashedCardNumber)->first()) {
                return self::handleError([__('api.card_already_exists')]);
            } else {
                $code = SmsHelper::generateCode();
                $msg = "Kod: " . $code . ". resusnasiya.uz kartani bog'lash uchun ruxsat so'radi " . CardHelper::getCardNumberMask($cardInfo['card_number']) . " Tel: " . callCenterNumber(2);

                [$result, $http_code] = SmsHelper::sendSms(correct_phone($card_phone), $msg);
                Log::info($result);

                if ($http_code === 200) {
                    $hashedCode = Hash::make($code);
                    $card = new Card();
                    $card->user_id = $user->id;
                    $card->card_number = env('test_API_BALANCE_SWITCH_BOOLEAN') ? EncryptHelper::encryptData(str_replace(' ', '', $request->card)) : EncryptHelper::encryptData($cardInfo['card_number']);
                    $card->card_valid_date = EncryptHelper::encryptData($cardInfo['expire']);
                    $card->card_name = $cardInfo['owner'];
                    $card->phone = correct_phone($card_phone);
                    $card->type = EncryptHelper::encryptData(CardHelper::checkTypeCard($cardInfo['card_number'])['name']);
                    $card->token = md5($hashedCode . uniqid('test'));
                    $card->guid = $hashedCardNumber;
                    $card->status = Card::CARD_INACTIVE;
                    $card->hidden = 0;
                    $card->card_number_prefix = env('test_API_BALANCE_SWITCH_BOOLEAN') ? substr(str_replace(' ', '', $request->card), 0, 8) : substr($cardInfo['card_number'], 0, 8);
                    if ($card->save()) {
                        Redis::set($card->token, $hashedCode);
                        $otpService = new OtpService(correct_phone($card_phone),$code);
                        $otpService->save_record();
                        $data['card_token'] = $card->token;
                        return self::handleResponse($data);
                    } else {
                        return self::handleError([__('api.internal_error') . ' ' . $cardInfo['card']]);
                    }
                }
            }
        }
        return self::handleError([__('panel/buyer.error_card_sms_off') . ' ' . $cardInfo['card']]);
    }

    public static function cardConfirm(Request $request)
    {
        $inputs = self::validatedCardConfirm($request);
        $hashedCode = Redis::get($inputs['card_token']);
        if (Hash::check($inputs['code'], $hashedCode)) {
            $card = Card::where('token', $request->card_token)->first();
            if (!$card) {
                return self::handleError([__('card.card_not_found')]);
            }
            $card_number = EncryptHelper::decryptData($card->card_number);
            $card->sms_code = $inputs['code'];
            $card->token = '';
            $card->status = Card::CARD_ACTIVE;
            $card->hidden = 1;
            $card->save();
            Redis::del($inputs['card_token']);
            return self::handleResponse([__('panel/buyer.txt_card_added')]);
        }
        return self::handleError([__('panel/buyer.bonus_sms_not_correct')]);
    }

    public static function cardAddV2(Request $request)
    {
        $inputs = self::validatedCardAdd($request);

        $validator = Validator::make($request->all(), [
            'card_number'     => [new CheckCardByPhone()]
        ]);
        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }

        $cardType = CardHelper::checkTypeCard($request->card_number)['type'];
        if ($cardType == 0) {
            return self::handleError([__('api.unknown_type_of_card')]);
        }
        $user = Auth::user();
        $request->merge(['card' => $inputs['card_number'], 'exp' => $inputs['card_valid_date']]);
        $cardInfo = MainCardService::getCardPhone($request);
        Log::channel('cards')->info('new CardAdd for ' . $user->id);
        Log::channel('cards')->info($cardInfo);
        if (!isset($cardInfo['result']) || $cardInfo['status'] == false) {
            return self::handleError([__('api.unknown_type_of_card')]);
        }
        $cardInfo = $cardInfo['result'];
        $card_phone = $cardInfo['phone'] ?? false; //номер смс информирования
        if ($card_phone) {
            if (mb_substr($card_phone, 8, 4) !== mb_substr(correct_phone($user->phone), 8, 4)) {
                self::handleError([__('panel/buyer.error_phone_not_equals')]);
            }
            $cardNumber = $request->card_number;
            $hashedCardNumber = md5($cardNumber);
            if ($card = Card::where('guid', $hashedCardNumber)->where('user_id',$user->id)->first()) {
                return self::handleError([__('api.card_already_exists')]);
            }

            $phone = correct_phone($card_phone);

            if (!OTPAttemptsHelper::isAvailableToSendOTP($phone)) {
                return self::handleError([__('mobile/v3/otp.attempts_timeout')]);
            }
            $code = SmsHelper::generateCode();
            $msg = "Kod: " . $code . ". resusnasiya.uz kartani bog'lash uchun ruxsat so'radi " . CardHelper::getCardNumberMask($cardInfo['card_number']) . " Tel: " . callCenterNumber(2);

            $phone = correct_phone($card_phone);

            [$result, $http_code] = SmsHelper::sendSms($phone, $msg);
            Log::info($result);

            if ($http_code === 200) {
                $hashedCode = Hash::make($code);
                $token = md5($hashedCode . uniqid('test'));

                $otpService = new OtpService($phone, $code);
                $otpService->save_record();

                Redis::set($token, $hashedCode);
                $data['card_token'] = $token;
                return self::handleResponse($data);
            }
            return self::handleError([__('panel/buyer.bonus_sms_service_unavailable')]);
        }
        return self::handleError([__('panel/buyer.error_card_sms_off') . ' ' . $cardInfo['card']]);
    }

    public static function cardConfirmV2(Request $request)
    {
        $inputs = self::validatedCardConfirm($request);
        $cardType = CardHelper::checkTypeCard($request->card_number)['type'];
        if ($cardType == 0) {
            return self::handleError([__('api.unknown_type_of_card')]);
        }
        $hashedCode = Redis::get($inputs['card_token']);
        $user = Auth::user();

        $hashResult = Hash::check($inputs['code'], $hashedCode);
        $request->merge(['card' => $request->card_number, 'exp' => $request->card_valid_date]);
        $cardInfo = MainCardService::getCardPhone($request);
        if (!isset($cardInfo['result']) || $cardInfo['status'] == false) {
            return self::handleError([__('api.unknown_type_of_card')]);

        }
        //remove extra spaces
        $correct_card_info = CardHelper::getAdditionalCardInfo($request->card_number, $request->card_valid_date);
        $cardInfo = $cardInfo['result'];
        $card_phone = $cardInfo['phone'] ?? null;

        if(isset($card_phone)) {
            $otpCheckResult = OTPAttemptsHelper::checkOtpCode(correct_phone($card_phone), $hashResult);
            if ($otpCheckResult['error']) {
                return self::handleError($otpCheckResult['message'],'error',400, $otpCheckResult['errorCode']);
            }
        }

        $card = new Card();
        $card->user_id = $user->id;
        $card->card_number = EncryptHelper::encryptData($correct_card_info['card']);
        $card->card_valid_date = EncryptHelper::encryptData($cardInfo['expire']);
        $card->card_name = $cardInfo['owner'];
        $card->phone = $card_phone;
        $card->type = EncryptHelper::encryptData(CardHelper::checkTypeCard($cardInfo['card_number'])['name']);
        $card->guid = md5($request->card_number);
        $card->status = Card::CARD_ACTIVE;
        $card->hidden = 1;
        $card->sms_code = $request->code;
        $card->card_number_prefix = substr($correct_card_info['card'], 0, 8);
        $card->save();

        Redis::del($inputs['card_token']);
        return self::handleResponse([__('panel/buyer.txt_card_added')]);
    }

    private static function accept(Request &$request)
    {

        if (!$request->has('verify') || is_null($request->verify)) return true; // ??? 22.04.2021 если поле не задано, например при регистарции пользвоателя
        $check = false;
        switch (CardHelper::checkTypeCard($request->card_number)['type']) {
            case 1:
                if ($request->has('verify')) {
                    $verifyData = json_decode(EncryptHelper::decryptData($request->verify), true);
                    $request->merge(['card_number' => $verifyData['pan'] ?? '',
                        'token' => $verifyData['id'] ?? '',
                        'phone' => $verifyData['phone'] ?? '',
                        'card_name' => $verifyData['fullName'] ?? ''
                    ]);
                    $check = true;
                }
                break;
            case 2:
                if ($request->has('verify')) {
                    $verifyData = json_decode(EncryptHelper::decryptData($request->verify), true);
                    $request->merge(['phone' => $verifyData['phone'] ?? '',
                        'card_name' => $verifyData['name'] ?? ''
                    ]);
                    $check = true;
                }
                break;
        }
        return $check;
    }

    public static function addMainCard(Request $request)
    {
        $user = User::with('roles')->find(Auth::user()->id);
        $is_vendor = BuyerService::is_vendor($user->role_id);
        if ($is_vendor) {
            $user = Buyer::where('phone', $request->phone)->first();
            if (!$user) {
                return self::handleError([__('auth.error_user_not_found')]);
            }
        }
        Log::channel('cards')->info('CardController.add buyer.card.add | scoring | KATM');
        Log::channel('cards')->info($request);
        $cardType = CardHelper::checkTypeCard($request->card_number)['type'];
        if ($cardType == 0) {
            return self::handleError([__('api.unknown_type_of_card')]);
        }
        $hashedCardNumber = md5($request->card_number);
        Log::channel('cards')->info('hash: ' . $hashedCardNumber);
        if (self::accept($request)) {
            $card_num = str_replace(' ', '', $request->card_number);
            Log::channel('cards')->info('card_num: ' . $card_num);
            // корректировка карты, удаление пробелов
            $cardInfo = CardHelper::getAdditionalCardInfo($card_num, $request->card_valid_date);
            Log::channel('cards')->info($cardInfo);
            $hashedCardNumber = md5($cardInfo['card']);
            if (!$buyerCard = Card::where('guid', $hashedCardNumber)->where('user_id',$user->id)->first()) {
                Log::channel('cards')->info('add card: ' . $cardInfo['card']);
                $buyerCard = new Card();
                $buyerCard->user_id = $user->id;
                $buyerCard->card_number = EncryptHelper::encryptData($card_num);
                $buyerCard->card_valid_date = EncryptHelper::encryptData($cardInfo['exp']);
                $buyerCard->card_name = $cardInfo['owner'] ?? '';
                $buyerCard->phone = correct_phone($user->phone);// EncryptHelper::encryptData($request->phone); // not encrypt
                $buyerCard->sms_code = $request->code;
                $buyerCard->type = EncryptHelper::encryptData(CardHelper::checkTypeCard($cardInfo['card'])['name']);
                $buyerCard->guid = $hashedCardNumber;
                $buyerCard->card_number_prefix = substr($card_num, 0, 8);
                if (!$buyerCard->save()) {
                    $result['status'] = 'error';
                    return $result;
                }
            }
            if ($request->has('token')) {
                $buyerCard->token = EncryptHelper::encryptData($request->token);
            }
            // здесь нужно получить скоринг по карте и записать его в бд
            // если скоринг не проводился
            Log::channel('cards')->info('buyersetting not found');
            Log::channel('cards')->info($user->id . '==' . $user->id);
            $day = date('d', time());
            if ($day < 25) {
                $months = ' -6 month'; // текущий месяц + еще 6 прошедших
            } else {
                $months = ' -5 month'; // текущий месяц + еще 5 прошедших
            }
            $to = date('Ym25', time());
            $from = date('Ym01', strtotime($to . $months));
            $request->merge([
                'info_card' => [
                    'card_number' => $cardInfo['card'],
                    'card_valid_date' => $cardInfo['exp']
                ],
                'start_date' => $from,
                'end_date' => $to
            ]);

            // true - пока для всех запросов на uzcard и humo
            $scoring = UniversalHelper::getScoring($request);
            Log::channel('cards')->info('OUT scoring status: ' . $scoring['status']);
            if ($scoring['status'] == 'success') {
                Log::channel('cards')->info('scoring result ' . $buyerCard->user_id . ' ' . $cardInfo['card']);
                Log::channel('cards')->info($scoring['scoring']);
                $scoringResult = $scoring['scoring']['scoring'];
                $scoring_ball = $scoring['scoring']['ball'];
                Log::channel('cards')->info('scoring for ' . $buyerCard->user_id . ' ' . $cardInfo['card']);
                Log::channel('cards')->info('scoring: ' . $scoringResult . ' ball: ' . $scoring_ball);
                // если скоринг не пройден не вносить информацию в БД
                if ($scoringResult > 0) {
                    // сохраняем скоринг
                    if (!$cardScoring = CardScoring::where('user_id', $user->id)->first()) $cardScoring = new CardScoring();
                    $cardScoring->user_id = $user->id;
                    $cardScoring->user_card_id = $buyerCard->id;
                    $cardScoring->period_start = $from;
                    $cardScoring->period_end = $to;
                    $cardScoring->status = $scoring['response']['status'];
                    if ($cardScoring->save()) {
                        Log::channel('cards')->info($cardScoring);
                        Log::channel('cards')->info('save cardScoring OK');
                    } else {
                        Log::channel('cards')->info('return status error not_save_card_scoring ' . $buyerCard->user_id . ' ' . $cardInfo['card']);
                        $result['status'] = 'error';
                        $result['info'] = 'error_save_scoring';
                        return $result;
                    }

                    // шифруем номер
                    $result = json_decode($scoring['request'], true);
                    $card_encrypt = EncryptHelper::encryptData($result['params']['card_number']);
                    $result['params']['card_number'] = $card_encrypt;

                    if (!$cardScoringLog = CardScoringLog::where('user_id', $user->id)->first()) $cardScoringLog = new CardScoringLog();
                    $cardScoringLog->card_scoring_id = $cardScoring->id;
                    $cardScoringLog->user_id = $user->id;
                    $cardScoringLog->status = $scoringResult > 0 ? 1 : 0; // $cardScoring->status;
                    $cardScoringLog->ball = $scoring_ball;
                    $cardScoringLog->scoring = $scoringResult;
                    $cardScoringLog->scoring_count = 1;
                    $cardScoringLog->card_hash = md5($cardInfo['card']);
                    $cardScoringLog->request = json_encode($result, JSON_UNESCAPED_UNICODE); // $scoring['request'];
                    $cardScoringLog->response = json_encode($scoring['response'], JSON_UNESCAPED_UNICODE);

                    if ($cardScoringLog->save()) {
                        Log::channel('cards')->info('scoring after ---------------------------------');
                        Log::channel('cards')->info('save cardScoringLog OK csl.id:' . $cardScoringLog->id);
                    } else {
                        Log::channel('cards')->info('return status error: error_save_card_scoring_log');
                        $result['status'] = 'error';
                        $result['info'] = 'error_save_card_scoring';
                        return $result;
                    }
                    Log::channel('cards')->info('buyer-settings');
                    //Buyer settings  - информация о лимит рассрочки
                    if (!$settings = BuyerSetting::where('user_id', $buyerCard->user_id)->first()) {
                        $settings = new BuyerSetting();
                    }
                    $settings->user_id = $buyerCard->user_id;
                    $settings->period = Config::get('test.buyer_defaults.period');
                    $settings->zcoin = 0;
                    $settings->personal_account = 0;
                    $settings->rating = 0;
//                        if($user->vip == 1){  // если вендор сам платит за клиента - лимит из конфига
//                            $limit = Config::get('test.vip_limit');  // 7000000
//                            $settings->limit = $limit;
//                            $settings->balance = $limit;
//                        }else{
//                            $settings->limit = $scoringResult;  // если это обычный вендор
//                            $settings->balance = $scoringResult;
//                        }
                    if (!$settings->save()) {
                        Log::channel('cards')->info('return status error: error_save_settings');
                        $result['status'] = 'error';
                        $result['info'] = 'error_save_settings';
                        return $result;
                    } else {
                        Log::channel('cards')->info('save BuyerSettings OK ' . $settings->user_id);
                    }
                    (new UserPayService)->createClearingAccount($buyerCard->user_id);
                    // статус пройденного скоринга
                    User::changeStatus($user, User::STATUS_CARD_ADD);
                    Log::channel('cards')->info('save buyer-settings buyer_id: ' . $settings->user_id . ' bs_id: ' . $settings->id);
                    Log::channel('cards')->info($settings);
                    // сохраняем карту
                    $buyerCard->save();

                } else { // scoring = 0
                    Log::channel('cards')->info('scoring 0');
                    Log::channel('cards')->info($scoringResult);
                    Log::channel('cards')->info('return status error: error_card_not_accept');
                    // сохраняем скоринг
                    if (!$cardScoring = CardScoring::where('user_id', $user->buyer_id)->first()) $cardScoring = new CardScoring();
                    $cardScoring->user_id = $user->id;
                    $cardScoring->user_card_id = $buyerCard->id;
                    $cardScoring->period_start = $from;
                    $cardScoring->period_end = $to;
                    $cardScoring->status = 0;
                    $cardScoring->save();
                    if (!$cardScoringLog = CardScoringLog::where('user_id', $user->id)->first()) $cardScoringLog = new CardScoringLog();
                    $cardScoringLog->card_scoring_id = $cardScoring->id;
                    $cardScoringLog->user_id = $user->id;
                    $cardScoringLog->status = 0;
                    $cardScoringLog->scoring = 0;
                    $cardScoringLog->ball = $scoring_ball;
                    $cardScoringLog->scoring_count = 1;

                    $cardScoringLog->card_hash = md5($cardInfo['card']);
                    $cardScoringLog->request = json_encode($scoring['request'], JSON_UNESCAPED_UNICODE); // $scoring['request'];
                    $cardScoringLog->response = json_encode($scoring['response'], JSON_UNESCAPED_UNICODE);
                    $cardScoringLog->save();

                    // если клиент вип, все равно даем ему лимит - из конфига
                    if($user->vip == 1){
                        $limit = Config::get('test.vip_limit');
                        if (!$settings = BuyerSetting::where('user_id', $buyerCard->user_id)->first()) {
                            $settings = new BuyerSetting();
                        }
                        $settings->user_id = $buyerCard->user_id;
                        $settings->period = Config::get('test.buyer_defaults.period');
                        $settings->zcoin = 0;
                        $settings->personal_account = 0;
                        $settings->rating = 0;
                        $settings->limit = $limit;
                        $settings->balance = $limit;
                        $settings->save();
                        (new UserPayService)->createClearingAccount($buyerCard->user_id);
                    }else{
                        $result['status'] = 'error';
                        $result['info'] = __('auth.error_card_not_accept');
                        return $result;
                    }
                }

            } else {
                Log::channel('cards')->info('return status error: service_unavailable');
                $result['status'] = 'error';
                $result['info'] = 'service_unavailable ENTER card again';
                $result['message'] = __('auth.service_unavailable');
                return $result;
            }

        } else {
            Log::channel('cards')->info('return status error: error_card_not_accept');
            $result['status'] = 'error';
            $result['message'] = __('auth.error_card_not_accept');
            return $result;
        }
        $result['status'] = 'success';
        $result['message'] = __('panel/buyer.txt_card_added');
        return $result;
    }

    public static function addMainCardWithoutScoring(Request $request)
    {
        $user = User::with('roles')->find(Auth::user()->id);

        $is_vendor = BuyerService::is_vendor($user->role_id);
        if ($is_vendor) {
            $user = Buyer::where('phone', $request->phone)->first();
            if (!$user) {
                return self::handleError([__('auth.error_user_not_found')]);
            }
        }

        Log::channel('cards')->info('Add main card without scoring');
        Log::channel('cards')->info($request);

        $cardType = CardHelper::checkTypeCard($request->card_number)['type'];
        if ($cardType == 0) {
            return self::handleError([__('api.unknown_type_of_card')]);
        }

        if (self::accept($request)) {

            $card_num = str_replace(' ', '', $request->card_number);
            Log::channel('cards')->info('card_num: ' . $card_num);

            // корректировка карты, удаление пробелов
            $cardInfo = CardHelper::getAdditionalCardInfo($card_num, $request->card_valid_date);
            Log::channel('cards')->info($cardInfo);

            $hashedCardNumber = md5($cardInfo['card']);

            $buyerCard = Card::where('guid', $hashedCardNumber)->where('user_id',$user->id)->first();

            if ($buyerCard) {

                return self::handleError([__('api.card_already_exists')]);

            } else {

                Log::channel('cards')->info('add card: ' . $cardInfo['card']);

                $buyerCard = new Card();
                $buyerCard->user_id = $user->id;
                $buyerCard->card_number = EncryptHelper::encryptData($card_num);
                $buyerCard->card_valid_date = EncryptHelper::encryptData($cardInfo['exp']);
                $buyerCard->card_name = $cardInfo['owner'] ?? '';
                $buyerCard->phone = correct_phone($user->phone);// EncryptHelper::encryptData($request->phone); // not encrypt
                $buyerCard->sms_code = $request->code;
                $buyerCard->type = EncryptHelper::encryptData(CardHelper::checkTypeCard($cardInfo['card'])['name']);
                $buyerCard->guid = $hashedCardNumber;
                if ($request->has('token')) {
                    $buyerCard->token = EncryptHelper::encryptData($request->token);
                }
                if (!$buyerCard->save()) {
                    $result['status'] = 'error';
                    return $result;
                }
            }

            User::changeStatus($user, 12);

        } else {

            Log::channel('cards')->info('return status error: error_card_not_accept');
            $result['status'] = 'error';
            $result['message'] = __('auth.error_card_not_accept');
            return $result;
        }

        $result['status'] = 'success';
        $result['message'] = __('panel/buyer.txt_card_added');
        return $result;
    }
}
