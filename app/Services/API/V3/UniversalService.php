<?php

namespace App\Services\API\V3;

use App\Classes\CURL\test\PaymentsRequest;
use App\Classes\CURL\Universal\PaymentsRequest as UniversalPaymentsRequest;
use App\Classes\CURL\test\PaymentsRevertRequest as testPaymentsRevertRequest;
use App\Classes\CURL\Universal\PaymentsRevertRequest as UniversalPaymentsRevertRequest;
use App\Classes\CURL\test\CardScoringRequest;
use App\Helpers\CardHelper;
use App\Helpers\EncryptHelper;
use App\Helpers\UniversalHelper;
use App\Models\Buyer;
use App\Models\Card;
use App\Models\CardScoring;
use App\Models\CardScoringLog;
use App\Models\Contract;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Validator;
use App\Rules\CheckCardValidDate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Log;

class UniversalService extends BaseService
{
    public static function validateCard(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card' => ['required', 'numeric','digits:16'],
            'exp'  => ['required', new CheckCardValidDate()],
            'phone' => 'sometimes|numeric|digits:12|regex:/(998)[0-9]{9}/',
        ]);
        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function validateCheckSmsCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'card_number' => ['required', 'numeric','digits:16'],
            'card_valid_date'  => ['required', new CheckCardValidDate()],
            'code'  => 'required|numeric|digits:6',
            'phone' => 'sometimes|numeric|digits:12|regex:/(998)[0-9]{9}/',
        ]);
        if ($validator->fails()) {
            return self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function sendSmsCodeUniversal(Request $request)
    {
        $inputs = self::validateCard($request);
        $user = User::with('roles')->find(Auth::user()->id);
        $is_vendor = BuyerService::is_vendor($user->role_id);
        if($is_vendor && !$request->has('phone')){
            return self::handleError([__('api.bad_request')]);
        }
        if($is_vendor){
            $user = Buyer::where('phone',$request->phone)->first();
            if(!$user){
                return self::handleError([__('auth.error_user_not_found')]);
            }
        }
        if($user->status == User::KYC_STATUS_VERIFY){
            return self::handleError([__('api.user_verified')]);
        }
        $cardType = CardHelper::checkTypeCard($request->card)['type'];
        if ($cardType == 0) {
            return self::handleError([__('api.unknown_type_of_card')]);
        }
        $card = Card::where('guid', md5($inputs['card']))->where('user_id',$user->id)->first();
        if($card){
            return self::handleError([__('api.card_already_exists')]);
        }
        Log::channel('cards')->info('start card.verify universal');
        $config = MainCardService::buildAuthorizeConfig();
        $id = 'test_' . uniqid(rand(), 1);
        $splitCardInfo = CardHelper::getAdditionalCardInfo($request->card, $request->exp);
        $input = json_encode([
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => 'card.register',
            'params' => [
                'card_number' => $splitCardInfo['card'],
                'expire' => $splitCardInfo['exp'],
            ],
        ]);
        $info = MainCardService::getCardPhone($request); // Universal
        Log::channel('cards')->info($info);
        if ((isset($info['error']['code']) && $info['error']['code'] == -31101)) {
            return self::handleError([__('panel/buyer.error_card_exp')]);
        }
        $user_phone = isset($request->phone) ? correct_phone($request->phone) : correct_phone($user->phone);
        $card_phone = $info['result']['phone'] ?? false;
        if ($card_phone) {
            $code_country = substr($card_phone, 0, 3);
            if ($code_country === '000') {
                $card_phone = '998' . substr($card_phone, 3);
            }
            // last 4 number
            if (mb_substr($user_phone, 8, 4) == mb_substr(correct_phone($card_phone), 8, 4)) {
                $cardNumber = $splitCardInfo['card'];
                $hashedCardNumber = md5($cardNumber);
                $card = Card::where('guid', $hashedCardNumber)->where('user_id',$user->id)->first();
                if (!$card) {
                    $isHumo = CardHelper::checkTypeCard($request->card)['type'] == 2;
                    if ($config['test_api_balance_switch']) {
                        if ((CardHelper::checkTypeCard($request->card)['type'] == 2) && env('HUMO_TO_UNIVERSAL_BALANCE_SWITCH')) {
                            $balanceResult = MainCardService::backOffice($input, $isHumo);
                        } elseif ((CardHelper::checkTypeCard($request->card)['type'] == 1) && env('UZCARD_TO_UNIVERSAL_BALANCE_SWITCH')) {
                            $balanceResult = MainCardService::backOffice($input, $isHumo);
                        } else {
                            $inputArr = \GuzzleHttp\json_decode($input);
                            $balanceResult = MainCardService::testApiGetBalance($inputArr->params->card_number, $inputArr->params->expire);
                        }
                    } else {
                        $balanceResult = MainCardService::backOffice($input, $isHumo);
                    }
                    if (isset($balanceResult['result'])) {
                        $balance = $balanceResult['result']['balance'];
                        if ($balance <= 100) {
                            self::handleError([__('panel/buyer.empty_balance')]);
                        }
                    } else {
                        return self::handleError([__('api.internal_error')]);
                    }
                    // 29.04.2021  Здесь выполняем скоринг карты, если она не проходит, то просим ввести другую карту!
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
                            'card_number' => $request['card'],
                            'card_valid_date' => $request['exp']
                        ],
                        'start_date' => $from,
                        'end_date' => $to,
                        'phone' => $user_phone,  // ?? user phone
                        'card_phone' => $card_phone,  // ?? card phone
                    ]);
                    $scoring_result = UniversalHelper::getScoring($request);  //->card_number, $request->card_valid_date, $from, $to, true);
                    Log::channel('cards')->info('scoring-result');
                    Log::channel('cards')->info(json_encode($scoring_result));
                    if (isset($scoring_result['response']['result'])) {
                        Log::channel('cards')->info('scoring from: ' . __CLASS__ . ' ' . __METHOD__ . ' ' . __LINE__);
                        $_scoring = UniversalHelper::scoringScore($scoring_result['response']['result']);
                        $scoringResult = $_scoring['scoring'];

                        if ($request->has('buyer_id')) {  // ??
                            $user_id = $request->buyer_id;
                        } else if ($request->has('phone')) { // если регистрирует вендор
                            $buyer = Buyer::where('phone', $request->phone)->first();
                            $vip = $buyer->vip;
                            $user_id = $buyer->id;
                        } else {   // если регистрируется сам клиент
                            $vip = $user->vip;
                            $user_id = $user->id;
                        }
                        $sum = isset($_scoring['sum']) ? $_scoring['sum'] : $_scoring['scoring'];
                        Log::channel('cards')->info('SCORING NOT CONFIRM buyer_id: ' . $user->id . ' :  sum: ' . $sum . ' ball: ' . $_scoring['ball']);
                        $scoring = new CardScoring();
                        $scoring->user_id = $user_id;
                        $scoring->user_card_id = 0; // данная карта не проходит, ее не храним в cards  - ??
                        $scoring->period_start = Carbon::parse($from)->format('Y-m-d H:i:s');
                        $scoring->period_end = Carbon::parse($to)->format('Y-m-d H:i:s');
                        $scoring->status = 1;
                        $scoring->save();

                        $scoringLog = new CardScoringLog();
                        $scoringLog->user_id = $user_id; // $user->id;
                        $scoringLog->card_scoring_id = $scoring->id;
                        $scoringLog->card_hash = md5($request['card']);
                        $scoringLog->request = $scoring_result['request'];
                        $scoringLog->response = json_encode($scoring_result['response'], JSON_UNESCAPED_UNICODE);
                        $scoringLog->scoring = $sum;
                        $scoringLog->ball = $_scoring['ball'];
                        $scoringLog->scoring_count = 1;
                        $scoringLog->status = (int)$scoringResult > 0;
                        $scoringLog->save();
                    } else {
                        return self::handleError([__('panel/buyer.error_scoring')]);
                    }
                    // если клиент вип - то все равно пропускаем карту
                    if ($scoringResult == 0 && $vip == 0) {
                        return self::handleError([__('panel/buyer.error_card_scoring')]);
                    }
                    // need string to give
                    $res = LoginService::sendSmsCode($user_phone, true, "Kod: :code. resusnasiya.uz kartani bog'lash uchun ruxsat so'radi **** " . mb_substr($request['card'], -4) . " Tel: " . callCenterNumber(2));
                    return $res;
                } else {
                    // если такая карта уже есть в системе, собираем по ней инфо (фио, тел и долг клиента)
                    $contracts = Contract::where('user_id', $card->user_id)->whereIn('status', [1, 3, 4])->with('schedule')->get();
                    $total_debt = 0;
                    foreach ($contracts as $contract) {
                        // сумма общего долга клиента
                        if ($schedules = $contract->schedule) {
                            foreach ($schedules as $schedule) {
                                if ($schedule->status == 1) continue;
                                $payment_date = strtotime($schedule->payment_date);
                                $now = strtotime(Carbon::now()->format('Y-m-d 23:59:59'));
                                if ($payment_date > $now) continue;
                                $total_debt += $schedule->balance;  // сумма общего долга клиента
                            }
                        }
                    }
                    $total_debt = number_format($total_debt, 2, '.', ' ');
                    $card_number = EncryptHelper::decryptData($card->card_number);
                    $exp = EncryptHelper::decryptData($card->card_valid_date);
                    $request = new Request();
                    $request->merge([
                        'card' => $card_number,
                        'exp' => $exp
                    ]);
                    $res = MainCardService::getCardPhone($request);
                    $result = [
                        'status' => 'error',
                        'info' => 'error_card_equal',
                        'card_data' => [
                            'card_owner' => $res['result']['owner'] ?? null,
                            'card_phone' => $res['result']['phone'] ?? null,
                            'total_debt' => $total_debt
                        ]
                    ];
                    return self::handleError([__('panel/buyer.error_card_equal')]);
                }
            } else {
                return self::handleError([__('panel/buyer.error_phone_not_equals')]);
            }
        }

        Log::channel('cards')->info('end humo.verify');
        return self::handleError([__('panel/buyer.error_card_sms_off')]);

    }

    public static function checkSmsCodeUniversal(Request $request)
    {
        Log::info('REQUEST check-sms-code-uz:');
        Log::info($request);
        $inputs = self::validateCheckSmsCode($request);
        $request->merge(['url_humo' => true, 'card' => $inputs['card_number'], 'exp' => $inputs['card_valid_date']]);

        $user = User::find(Auth::user()->id);
        $is_vendor = BuyerService::is_vendor($user->role_id);
        if($is_vendor && !$request->has('phone')){
            return self::handleError([__('api.bad_request')]);
        }
        if($is_vendor){
            $user = Buyer::where('phone',$request->phone)->first();
            if(!$user){
                return self::handleError([__('auth.error_user_not_found')]);
            }
        }
        $encSms = LoginService::checkSmsCode($request);
        if ($encSms['code'] == 1) {
            $result = CardService::addMainCardWithoutScoring($request);
            if (isset($result['status']) && $result['status'] == 'success') {
                $info = MainCardService::getCardPhone($request);
                $last_card = Card::where('user_id', $user->id)->orderBy('id', 'DESC')->first();
                if($last_card){
                    $last_card->card_name = $info['result']['owner'] ?? '';
                    $last_card->save();
                }
                $user->status = 12;
                $user->save();
                return self::handleResponse(isset($result['message']) ? [$result['message']] : []);
            }
            return self::handleError(isset($result['message']) ? [$result['message']] : [__('api.bad_request')]);
        }
        return self::handleError($encSms['error'] ?? [__('api.bad_request')]);
    }

    public static function sendSmsCodeCodeWithoutScoring(Request $request)
    {
        $inputs = self::validateCard($request);
        $user = User::find(Auth::user()->id);
        $is_vendor = BuyerService::is_vendor($user->role_id);
        if($is_vendor && !$request->has('phone')){
            return self::handleError([__('api.bad_request')]);
        }
        if($is_vendor){
            $user = Buyer::where('phone',$request->phone)->first();
            if(!$user){
                return self::handleError([__('auth.error_user_not_found')]);
            }
        }
        if($user->status == User::KYC_STATUS_VERIFY){
            return self::handleError([__('api.user_verified')]);
        }
        $cardType = CardHelper::checkTypeCard($request->card)['type'];
        if ($cardType == 0) {
            return self::handleError([__('api.unknown_type_of_card')]);
        }
        $card = Card::where('guid', md5($inputs['card']))->where('user_id',$user->id)->first();
        if($card){
            return self::handleError([__('api.card_already_exists')]);
        }
        Log::channel('cards')->info('start card.verify universal');
        $splitCardInfo = CardHelper::getAdditionalCardInfo($request->card, $request->exp);
        $info = MainCardService::getCardPhone($request);
        Log::channel('cards')->info($info);
        if ((isset($info['error']['code']) && $info['error']['code'] == -31101)) {
            return self::handleError([__('panel/buyer.error_card_exp')]);
        }
        $user_phone = isset($request->phone) ? correct_phone($request->phone) : correct_phone($user->phone);
        $card_phone = $info['result']['phone'] ?? false;
        if ($card_phone) {
            $code_country = substr($card_phone, 0, 3);
            if ($code_country === '000') {
                $card_phone = '998' . substr($card_phone, 3);
            }

            // last 4 number
            if (mb_substr($user_phone, 8, 4) == mb_substr(correct_phone($card_phone), 8, 4)) {
                $card_number = $splitCardInfo['card'];
                $expire = $splitCardInfo['exp'];
                $hashedCardNumber = md5($card_number);
                $card = Card::where('guid', $hashedCardNumber)->where('user_id',$user->id)->first();
                if (!$card) {
                    self::payAndReturnMoneyFromCard(1,$card_number,$expire);
                    self::checkLastMonthsIncome($card_number, $expire);

                    return LoginService::sendSmsCode($user_phone, true, "Kod: :code. resusnasiya.uz kartani bog'lash uchun ruxsat so'radi **** " . mb_substr($request['card'], -4) . ' Tel: ' . callCenterNumber(2));
                }
                return self::handleError([__('panel/buyer.error_card_equal')]);
            }
            return self::handleError([__('panel/buyer.error_phone_not_equals')]);
        }
        return self::handleError([__('panel/buyer.error_card_sms_off')]);
    }

    public static function payAndReturnMoneyFromCard(float $amount, string $card_number,string $expire)
    {
        // payment for given amount
        Log::channel('cards')->info('Start card write-off check for card: '.$card_number);
        $requestType = '';
        try {
            $paymentRequest = new PaymentsRequest($card_number, $expire, $amount);
            $paymentRequest->execute();
            if ($paymentRequest->isSuccessful()) {
                $requestType = CardScoringLog::RESPONSE_test;
            }
        } catch (\Exception $e) {
            Log::channel('cards')->info('Не удалось списать через сервис test: '.$card_number);
        }

        if ($requestType == '') {
            try {
                $paymentRequest = new UniversalPaymentsRequest($card_number, $expire, $amount);
                $paymentRequest->execute();
                if ($paymentRequest->isSuccessful()) {
                    $requestType = CardScoringLog::RESPONSE_UNIVERSAL;
                }
            } catch (\Exception $e) {
                Log::channel('cards')->info('Не удалось списать через сервис Universal: '.$card_number);
            }
        }
        if (!$requestType) {
            return self::handleError([__('panel/buyer.error_write_off_failed')]);
        }
        Log::channel('cards')->info('Successful 1 sum write-off for card: '.$card_number);
        // pop-up for amount
        $paymentID = $paymentRequest->response()->paymentID();
        $uuid = $paymentRequest->response()->paymentUUID();

        if ($requestType == CardScoringLog::RESPONSE_test) {
            $paymentsRevertRequest = new testPaymentsRevertRequest($paymentID);
        }
        if ($requestType == CardScoringLog::RESPONSE_UNIVERSAL) {
            $paymentsRevertRequest = new UniversalPaymentsRevertRequest($card_number, $paymentID, $uuid);
        }
        $paymentsRevertRequest->execute();
        if (!$paymentsRevertRequest->isSuccessful()) {
            Log::channel('cards')->info('Не удалось пополнить карту на 1 сум: '.$card_number);
            return self::handleError([__('panel/buyer.error_write_off_failed')]);
        }
        Log::channel('cards')->info('Successful 1 sum pay back for card: '.$card_number);
        return true;
    }

    public static function checkLastMonthsIncome(string $card_number,string $expire)
    {
        /* Card Scoring */
        Log::channel('cards')->info('Start card scoring: '.$card_number);

        try {
            $cardScoringRequest = (new CardScoringRequest($card_number, $expire))->execute();
        } catch (\Exception $e) {
            Log::channel('cards')->info('Card scoring ('.$card_number.') failed (Bad response)');
            return self::handleError([__('panel/buyer.error_scoring')]);
        }

        if ($cardScoringRequest->isSuccessful()) {
            $cardScoringResponse = $cardScoringRequest->response();
            $lastMonthsIncomeIsValid = $cardScoringResponse->checkLastMonthsIncome();
            Log::channel('cards')->info('Card scoring ('.$card_number.'):', $cardScoringResponse->json());
            if (!$lastMonthsIncomeIsValid) {
                Log::channel('cards')->info('Card scoring ('.$card_number.') failed');
                return self::handleError([__('panel/buyer.error_card_scoring')]);
            }
            Log::channel('cards')->info('Card scoring ('.$card_number.') is successful');
        } else {
            Log::channel('cards')->info('Card scoring ('.$card_number.') failed (Bad response)');
            return self::handleError([__('panel/buyer.error_scoring')]);
        }

        return true;
    }
}
