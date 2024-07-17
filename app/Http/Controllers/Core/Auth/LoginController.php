<?php

namespace App\Http\Controllers\Core\Auth;

use App\Http\Requests\LoginRequest;
use App\Models\Cart;
use App\Models\Company;
use App\Models\GeneralCompany;
use App\Models\KycHistory;
use App\Models\Role;
use App\Services\API\V3\BuyerService;
use App\Services\API\V3\LoginService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;

use App\Models\User;
use App\Models\BuyerSetting;
use App\Models\BuyerPersonal;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class LoginController extends AuthController
{

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Validation rules for check user
     *
     * @var string[]
     */
    private $validatorRules = [
    ];

    private $config;


    public function __construct()
    {
        $this->config = Config::get('test.buyer_defaults');
    }

    /**
     * @OA\Post(
     *      path="/login/auth",
     *      operationId="auth",
     *      tags={"Authorization"},
     *      summary="Authorization user by phone or id and password",
     *      description="Return token",
     *      @OA\Parameter(
     *          name="phone",
     *          description="Phone",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="code",
     *          description="SMS code",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="partner_id",
     *          description="Partner ID",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="password",
     *          description="Password",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              format="password"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *       response=201,
     *       description="Success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    /**
     * Login user after all check
     * Method sms or password
     *
     * @param Request $request
     * @return array|bool|false|string
     */
    public function auth(LoginRequest $request)
    {
        $response = [];
        $company = null;
        if ($request->has('code') || $request->has('password')) {
            if ($request->has('partner_id')) {
                $company = Company::find($request->partner_id);
                $user = $company->user;
            } else {
                if ($user = User::where("phone", $request->phone)->first()) {
                    if ($user->id == 1005 && $request->code == 9898) { // for APPLE/GOOGLE ACCOUNT
                        //Авторизация пользователя
                        Auth::login($user);
                        $this->result['status'] = 'success';
                        $this->result['data']['user_status'] = $user->status;
                        $this->result['data']['user_id'] = $user->id;
                        $this->result['data']['api_token'] = $user->api_token;
                        return $this->result();
                    }
                }
            }

            if ($user == null && ($request->role == '' || $request->role == 'buyer')) {
                if ($request->has("code")) {
                    $response = $this->checkSmsCode($request);
                    if ($response['status'] == 'success') {
                        //Регистрация покупателя
                        $this->registerAndAuth($request);
                        return $this->result();
                    }
                }

                $this->result['status'] = 'error';
                $this->message('danger', __('auth.error_user_not_found'));
            } else {
                //Проверка авторизации
                if (($user->status_employee === 0 && $request->role == 'employee') ||
                    ($user->status === 8 && $user->status === 9 && $request->role == 'buyer') ||
                    ($company != null && $company->status === 0)) { // если статус 0 то запретить дальнейшие действия и уведомить о неактивности

                    $this->result['status'] = 'error';
                    $this->message('danger', __('auth.error_user_inactive'));
                } else {
                    if ($request->has('password')) {
                        $response = $this->checkPassword($request);
                    } elseif ($request->has("code")) {
                        $response = $this->checkSmsCode($request);
                    }

                    if ($response['status'] == 'success') {
                        //Прикрепляем к пользователю роль ПОКУПАТЕЛЬ, если у него ее нет
                        if (!$user->hasRole('buyer') && $request->role == 'buyer') {
                            $user->attachRole('buyer');
                            $this->setDefaultInfo($user);
                        }

                        $user->lang = $request->lang;
                        $user->device_os = $request->system;
                        if ($request->system == 'ios') {
                            $user->firebase_token_ios = $request->fcm_token;
                        } elseif ($request->system == 'android') {
                            $user->firebase_token_android = $request->fcm_token;
                        }
                        $user->save();

                        //Авторизация пользователя
                        Auth::login($user);
                        $this->result['status'] = 'success';
                        $this->result['data']['user_status'] = $user->status;
                        $this->result['data']['user_id'] = $user->id;
                        $this->result['data']['api_token'] = $user->api_token;
                    }
                }
            }

            if (Session::isStarted()) {
                $cartSession = null;
                $cartID = null;

                $cartSessionID = Cookie::get('cart') ?? request()->session()->get('cart');

                if ($cartSessionID) {
                    $cartSession = Cart::where(['cart_id' => $cartSessionID])->whereNull('user_id')->get();
                }

                if (!$user) {
                    $user = Auth::user();
                }

                $userCart = $user->cartProducts;

                if ($userCart->isNotEmpty()) {
                    $userCartID = $userCart->first()->cart_id;
                }

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

                if ($user->hasRole('sales-manager')) {
                    session(['role' => 'sales-manager', 'cart' => $cartID]);
                } else {
                    session(['role' => $request->role, 'cart' => $cartID]);
                }

                Cookie::queue(Cookie::make('cart', $cartID, 60 * 24 * 365));
            }
        } else {
            $this->result['status'] = 'error';
            $this->message('danger', __('auth.error_empty_password_or_code'));
        }
        return $this->result();
    }


    /**
     * Register and auth buyer
     *
     * @param Request $request
     */
    public function registerAndAuth(Request $request)
    {
        $phone = correct_phone($request->phone);
        $system = $request->system ?? 'web';
        $user = LoginService::createBuyer($phone,$system);

        Auth::login($user);

        $this->result['status'] = 'success';
        $this->result['data']['id'] = $user->id;
        $this->result['data']['user_status'] = $user->status;
        $this->result['data']['api_token'] = $user->api_token;
    }


    /**
     * Check user input password correct or incorrect
     * @param Request $request
     * @return array|bool|false|string
     */
    public function checkPassword(LoginRequest $request)
    {
        if ($request->has('partner_id')) {
            $user = User::where("company_id", $request->partner_id)->first();
            if(Config::get('test.is_active_new_merchant_web') && Hash::check($request->password, $user->password)) {
                $new_merchant_link = Config::get('test.new_merchant_web_link') . '?token='.$user->api_token;
                return response($new_merchant_link,302);
            }
        }
        else
            $user = User::where("phone", $request->phone)->first();

        if (Hash::check($request->password, $user->password)) {
            $this->result['status'] = 'success';
        } else {
            $this->result['status'] = 'error';
            $this->message('danger', __('auth.error_password_wrong'));
        }
        return $this->result();
    }

    private function setDefaultInfo($user)
    {
        //Create personals
        $personals = new BuyerPersonal();
        $personals->user_id = $user->id;
        $personals->save();

        //Create settings
        /* $settings = new BuyerSetting();
        $settings->user_id =            $user->id;
        $settings->limit =              $this->config['limit'];
        $settings->period =             $this->config['period'];
        $settings->balance =            $this->config['limit'];
        $settings->zcoin =              0;
        $settings->personal_account =   0;
        $settings->rating =             0;

        $settings->save(); */
    }

    /**
     * Validate user in register site
     * @param Request $request
     * @return array|bool|false|string
     */
    public function validateForm(Request $request)
    {
        $IsPartner = $request->has('partner_id');

        if ($IsPartner) {
            $messages = [
                'partner_id.exists' => Lang::get('auth.error_company_is_empty')
            ];
            $customRules = [
                'partner_id' => ['required', 'string', 'max:30', 'regex:/^[0-9]+$/', 'exists:companies,id'],
            ];
        } else {
            $customRules = [
                'phone' => ['required', 'string', 'max:15', 'regex:/^\+?[0-9]+$/', 'exists:users,phone'],
            ];
            $messages = [
                'phone.exists' => Lang::get('auth.error_phone_is_empty')
            ];
        }
        $validator = $this->validator($request->all(), $this->validatorRules, $customRules, $messages);

        if ($validator->fails()) { // error

            //Log::channel('errors')->info('ошибка validator->errors');
            //Log::channel('errors')->info($validator->errors());

            if ($IsPartner) {
                $this->result['status'] = 'error';
                $this->result['response']['errors'] = $validator->errors();
            } elseif ($request->has('role') && $request->role == 'buyer') {
                $this->result['status'] = 'success';
                $this->result['response']['auth_type'] = 'sms';
                $this->result['response']['code'] = 404;
            } else {
                $this->result['status'] = 'error';
                $this->result['_info'] = 'else ';
                $this->result['response']['errors'] = json_encode($validator->errors(), JSON_UNESCAPED_UNICODE);
                $this->message('danger', __('auth.error_user_not_found'));
            }
        } else {
            if ($IsPartner) {
                $company = Company::find($request->partner_id);
                $user = $company->user;
            } else {
                $user = User::where("phone", $request->phone)->first();
            }

            $this->result['status'] = 'success';

            if ($request->has('role')) {
                if (($request->role == 'partner' && $user->hasRole('partner')) || ($request->role == 'partner' && BuyerService::is_vendor($user->role_id))) {
                    $this->result['company_description'] = $company->description;
                    $this->result['company_name'] = $company->name;
                    $this->result['company_inn'] = $company->inn;
                    $this->result['user_token'] = $user->api_token;
                }

                if ($request->role == 'buyer') {
                    $this->result['response']['auth_type'] = 'sms';
                } elseif (
                    $user->hasRole('sales-manager')
                    || ($request->role == 'partner' && $user->hasRole('partner'))
                    || ($request->role == 'employee' && $user->hasRole('employee'))
                    || $user->hasRole('ed_employee')
                    || $user->hasRole('admin')
                ) {
                    $this->result['response']['auth_type'] = 'pwd';
                } elseif ($request->role == 'partner' && BuyerService::is_vendor($user->role_id)) {
                    $this->result['response']['auth_type'] = 'pwd';
                } else {
                    $this->result['status'] = 'error';
                    $this->message('danger', __('auth.error_user_not_found'));
                }
            }
        }

        return $this->result();
    }

    public function invite(Request $request)
    {
        if ($invite_id = $request->get('user_id')) {
            if ($user = User::find($invite_id)) {
                $user->settings->zcoin += 0; //
                $user->settings->save();
            }
        }

        return $this->redirect('login');
    }

    public function sendSmsCode(Request $request, bool $sendCode = true, $msg = null, $len = 6)
    {
        return parent::sendSmsCode($request, $sendCode, $msg, $len);
    }


}
