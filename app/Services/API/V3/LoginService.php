<?php

namespace App\Services\API\V3;

use App\Helpers\SmsHelper;
use App\Helpers\V3\OTPAttemptsHelper;
use App\Models\BuyerAddress;
use App\Models\BuyerPersonal;
use App\Models\BuyerSetting;
use App\Models\Cart;
use App\Models\Company;
use App\Models\KycHistory;
use App\Models\User;
use App\Models\UserCreator;
use App\Models\V3\OtpEnterCodeAttempts;
use App\Services\Mobile\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class LoginService extends BaseService
{

    public static function validateSendSmsCode(Request $request)
    {
        $inputs = $request->only('phone');
        $validator = Validator::make($inputs, [
            'phone' => 'required|numeric|digits:12|regex:/(998)[0-9]{9}/',
        ]);
        if ($validator->fails()) {
            self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function validateAuth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric|digits:12|regex:/(998)[0-9]{9}/',
            'code' => 'nullable|numeric',
            'partner_id' => 'nullable|integer',
            'company_id' => 'nullable|integer'
        ]);
        if ($validator->fails()) {
            self::handleError($validator->errors()->getMessages());
        }
        if (!$request->has('password') && !$request->has('code')) {
            self::handleError([__('auth.error_empty_password_or_code')]);
        }
        return $validator->validated();
    }

    public static function sendSmsCode($phone, $sendCode = true, $msg = null, $len = 6)
    {
        $phone = correct_phone($phone);
        $user = User::where('phone', $phone)->first();

        if ($user && $user->status == User::KYC_STATUS_BLOCKED) {
            return self::handleError([__('cabinet/cabinet.you_blocked')]);
        }

        if (!OTPAttemptsHelper::isAvailableToSendOTP($phone)) {
            return self::handleError([__('mobile/v3/otp.attempts_timeout')]);
        }

        if($phone === Config::get('test.otp_phone_number_for_access_playmarket')){
            $code = Config::get('test.opt_code_for_access_playmarket');
        }
        else {
            $code = OTPAttemptsHelper::generateCode($len);
        }
        if ($sendCode) {
            if (!$msg) {
                $msg = "Kod: {$code}. resusnasiya.uz Platformasiga xush kelibsiz! Tel: " . callCenterNumber(2);
            } else {
                $msg = str_replace(':code', $code, $msg);
            }
        }

        // Добавил firebase хэш для авто считывания смс кода
        $msg = '<#> ' . $msg . PHP_EOL . Config::get('test.firebase_sms_code_key_hash_prod'); // adding firebase hash for autofill otp and replace code to next row

        $hashedCode = Hash::make($phone . $code);
        if ($sendCode) {
            [$result, $http_code] = SmsHelper::sendSms($phone, $msg);
            Log::info($result);

            if ($http_code === 200) {
                $otpService = new OtpService($phone,$code);
                $otpService->save_record();
                try {
                    Redis::set($phone, $hashedCode,'EX', '60');
                } catch (\Exception $e) {
                    dd($e);
                }
                return self::handleResponse(['hashed' => $hashedCode,'is_registered' => (bool)$user]);
            }
        }

        if (!$sendCode) {
            return self::handleResponse(['hashed' => $hashedCode]);
        }
        return self::handleError([__('panel/buyer.bonus_sms_service_unavailable')]);
    }

    public static function registerAndAuth(Request $request): array
    {
        $phone = correct_phone($request->phone);
        $system = $request->system ?? 'web';

        $company_id = $request->company_id ?? 0;
        $user = self::createBuyer($phone,$system,5,$company_id);

        Auth::login($user);

        $record = OtpEnterCodeAttempts::where('phone', correct_phone($request->phone))->first();

        if(isset($record)) {
            $record->update(['user_id' => $user->id]);
        }

        return [
            'user_status' => $user->status,
            'user_id' => $user->id,
            'api_token' => $user->api_token,
            'address_is_received' => 0,
            'is_registered' => false
        ];
    }

    private static function setDefaultInfo($user)
    {
        //Create personals
        $personals = new BuyerPersonal();
        $personals->user_id = $user->id;
        $personals->save();
    }

    public static function generateApiToken(User $user)
    {
        if ($user) {
            $now = date('Y-m-d H:i:s');
            $user->token_generated_at = $now;
            $user->api_token = md5(Hash::make($user->phone . $now));
            $user->save();
            //Create a record to know, from which app user came up
            UserCreator::create([
                'user_id' => $user->id,
                'creator_id' => \request()->has('creator_id') ? (int) \request()->get('creator_id') : null,
                'ip_address' => \request()->ip(),
            ]);
            return true;
        }
        Log::info('no user - no token');
        return false;
    }

    public static function checkSmsCode(Request $request)
    {
        $code = $request->input('code');
        $phone = $request->input('phone');
        if (!$phone) {
            $user = Auth::user();
            if (!$user) {
                return ['code' => 0, ''];
            }
            $phone = $user ? correct_phone($user->phone) : null;
        }

        $hashedCode = $request->has('hashedCode') ? $request->input('hashedCode') : Redis::get($phone);

        $result = Hash::check($phone . $code, $hashedCode);

        if ($result) {
            $data['code'] = 1;
            Redis::del($phone);
        } else {
            $data['code'] = 0;
            $data['error'] = [__('auth.error_code_wrong')];
        }

        //Проверка на лимит ввода и ограничение на ввод сообщений
        $otpCodeResponse = OTPAttemptsHelper::checkOtpCode($phone, boolval($data['code']));

        if ($otpCodeResponse['error']) {
            $data['code'] = 0;
            $data['error'] = $otpCodeResponse['message'];
            $data['data']['errorCode'] = $otpCodeResponse['errorCode'];
        }

        return $data;
    }

    public static function checkPassword(Request $request)
    {
        $phone = $request->phone;
        if ($request->has('partner_id')) {
            $company = Company::find($request->partner_id);
            $phone = $company->user->getAttributes()['phone'];
        }
        $user = User::where("phone", $phone)->first();

        if ($request->partner_id == '215037' && $request->password == '123456') { // для теста партнера
            return ['code' => 1];
        }

        if (Hash::check($request->password, $user->password)) {
            return ['code' => 1];
        }

        return ['code' => 0, 'error' => [__('auth.error_password_wrong')]];
    }

    public static function auth(Request $request)
    {
        $inputs = self::validateAuth($request);
        $data = [];
        $response = [];
        $company = null;
        if (isset($inputs['partner_id'])) {
            $user = User::where('company_id', $inputs['partner_id'])->first();
        } else {
            $user = User::where("phone", $inputs['phone'])->first();
        }

        if ($user == null && ($request->role == '' || $request->role == 'buyer')) {
            if (isset($inputs['code'])) {
                $response = self::checkSmsCode($request);
                if ($response['code'] == 1) {
                    //Регистрация покупателя
                    $data = self::registerAndAuth($request);
                    return self::handleResponse($data);
                }
                return self::handleError([__('auth.error_code_wrong')]);
            }
            return self::handleError([__('auth.error_user_not_found')]);
        }

        if (!$user) {
            return self::handleError([__('auth.error_user_not_found')]);
        }
        //Проверка авторизации
        if (($user->status_employee === 0 && $request->role == 'employee') ||
            ($user->status === 8 && $user->status === 9 && $request->role == 'buyer') ||
            ($company != null && $company->status === 0)
        ) { // если статус 0 то запретить дальнейшие действия и уведомить о неактивности
            return self::handleError([__('auth.error_user_inactive')]);
        }
        if ($request->has('password')) {
            $response = self::checkPassword($request);
        }
        if ($request->has('code') || $request->has('hashedCode')) {
            $response = self::checkSmsCode($request);
        }
        if ($response['code'] == 1) {
            //Прикрепляем к пользователю роль ПОКУПАТЕЛЬ, если у него ее нет
            if (!$user->hasRole('buyer') && $request->role == 'buyer') {
                $user->attachRole('buyer');
                self::setDefaultInfo($user);
            }
            $user->lang = $request->lang;
            $user->device_os = $request->system;
            if ($request->system == 'ios') {
                $user->firebase_token_ios = $request->fcm_token;
            }
            if ($request->system == 'android') {
                $user->firebase_token_android = $request->fcm_token;
            }
            $user->save();
            //Авторизация пользователя
            Auth::login($user);
            //If user logged out (api_toke == null)
            if ($user->api_token == null) {
                self::generateApiToken($user);
            }
            $data['user_status'] = $user->status;
            $data['user_id'] = $user->id;
            $data['api_token'] = $user->api_token;
            $buyer_address = BuyerAddress::where('type','registration')->where('user_id',$user->id)->first();
            $data['address_is_received'] = $buyer_address && $buyer_address->address !== null ? 1 : 0;
            $data['is_registered'] = true;
        } else {
            return self::handleError($response['error'],'error',400, $response['data']);
        }

        if (Session::isStarted()) {
            $cartSession = null;
            $cartID = null;

            $cartSessionID = Cookie::get('cart') ?? request()->session()->get('cart');

            if ($cartSessionID) {
                $cartSession = Cart::where(['cart_id' => $cartSessionID])->whereNull('user_id')->get();
            }

            if (!$user) $user = Auth::user();

            $userCart = $user->cartProducts;

            if ($userCart->isNotEmpty())
                $userCartID = $userCart->first()->cart_id;

            if ($cartSession) {
                foreach ($cartSession as $cart) {
                    $cartID = $userCartID ?? $cart->cart_id;
                    $cartUserProduct = Cart::where(['user_id' => $user->id, 'product_id' => $cart->product_id])->first();
                    if ($cartUserProduct) {
                        $cartUserProduct->quantity += $cart->quantity;
                    } else {
                        $cartUserProduct = new Cart();
                        $cartUserProduct->cart_id = $cartID;
                        $cartUserProduct->user_id = $user->id;
                        $cartUserProduct->product_id = $cart->product_id;
                        $cartUserProduct->quantity = $cart->quantity;
                    }
                    $cartUserProduct->save();
                    $cart->forceDelete();
                }
            } else {
                $cartID = $userCartID ?? md5(rand(0, 100000) . time()); // 07.04 - Добавили time() для уникальности
            }

            //request()->session()->put('cart', $cartID);

            if (Auth::user()->hasRole('sales-manager'))
                session(['role' => 'sales-manager', 'cart' => $cartID]);
            else
                session(['role' => $request->role, 'cart' => $cartID]);

            Cookie::queue(Cookie::make('cart', $cartID, 60 * 24 * 365));
        }
        return self::handleResponse($data);
    }

    public static function me(Request $request)
    {
        return self::handleResponse(Auth::user());
    }

    public static function logout(Request $request)
    {
        $user = Auth::user();
        $user->firebase_token_ios = null;
        $user->firebase_token_android = null;
        $user->device_os = null;
        // $user->api_token = null;
        $user->save();
        return self::handleResponse();
    }

    public static function createBuyer(int $phone,string $system , int $user_status = 1, int $company_id = 0):User {
        $user = new User();
        $user->phone = $phone;
        $user->status = $user_status;
        $user->device_os = $system;
        $user->doc_path = 1; //  файлы новом сервере
        $user->created_by = !empty($company_id) ? $company_id : null;
        $user->save();

        KycHistory::insertHistory($user->id, User::KYC_STATUS_CREATE);

        Log::info('user create LoginController->registerAndAuth user_id: ' . $user->id);

        self::setDefaultInfo($user);

        self::generateApiToken($user);

        $user->attachRole('buyer');

        BuyerSetting::create(['user_id' => $user->id]);

        (new UserPayService)->createClearingAccount($user->id);

        return $user;
    }
}
