<?php

namespace App\Http\Controllers\Core\Auth;

use App\Helpers\SmsHelper;
use App\Helpers\V3\OTPAttemptsHelper;
use App\Models\BuyerSetting;
use App\Models\Company;
use App\Models\KycHistory;
use App\Models\PartnerSetting;
use App\Models\User;
use App\Services\API\V3\BaseService;
use App\Services\Mobile\OtpService;
use App\Services\API\V3\UserPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class RegisterController extends AuthController {

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    private $validatorRules = [

    ];


    /**
     * @param Request $request
     * @return array|false|string
     */
    public function add( Request $request ) {

        $validator = $this->validator( $request->all(), $this->validatorRules );

        if ( $validator->fails() ) {
            // Validator error
            $this->result['status']             = 'error';
            $this->result['response']['errors'] = $validator->errors();

        } else {
            //$phone = $request->phone;

            //Creating user
            $user = new User();
            $user->kyc_status = User::KYC_STATUS_CREATE;
            $user->device_os = $request->system ?? 'web';

            $user->doc_path = 1; //  файлы  на новом сервере
            $user->save();

            Log::info('user create RegisterController->add user_id: ' . $user->id);

            KycHistory::insertHistory($user->id,User::KYC_STATUS_CREATE);

            //If partner
            if($request->has('partner')){
                $partner = $request->partner;

                //Creating company
                $company = new Company();
                foreach ($partner['company'] as $k=>$v)
                    $company->{$k} = $v;
                $company->save();

                //Creating partner settings
                $partnerSettings = new PartnerSetting();
                $partnerSettings->company_id    = $company->id;
                $partnerSettings->discount_3    = 0;
                $partnerSettings->discount_6    = 0;
                $partnerSettings->discount_9    = 0;
                $partnerSettings->discount_12   = 0;
                $partnerSettings->nds           = 1;

                //Markups
                $plans = Config::get('test.plans');
                foreach($plans as $plan=>$percent)
                    $partnerSettings['markup_'.$plan] = $percent;
                $partnerSettings->save();

                //Updating user

                $user->phone = partner_phone_short($request->phone); // mb_substr($request->phone,3);

                $user->name = $partner['name'];
                $user->surname = $partner['surname'];
                //$user->patronymic = $partner['patronymic'];
                $user->company_id = $company->id;
                $user->status = 1;

                $user->attachRole('partner');
            }else {
                BuyerSetting::create(['user_id' => $user->id]);
                (new UserPayService)->createClearingAccount($user->id);
                $user->attachRole('buyer');
                $user->phone = $request->phone;
            }

            $user->save();
            //Log::info('register');

            //Token generation
            self::generateApiToken($user);
            /*
                        if($request->has('partner'))
                            $user->attachRole('partner');
                        else $user->attachRole('buyer');*/

            $this->result['status'] = 'success';
            $this->result['data']   = $user;
            $this->message( 'success', __( 'auth.txt_request_successful' ) );

            //Log:info('result');
            //Log($this->result());


        }

        return $this->result();

    }



    /**
     * @param Request $request
     * @return array|false|string
     */
    public function validateForm( Request $request ) {

        $messages = [
            'phone.unique' => Lang::get('auth.error_phone_is_used')
        ];

        $customRules = [
            'phone'      => [ 'required', 'string', 'max:15', 'regex:/^\+?[0-9]+$/', 'unique:users' ]
        ];

        $validator = $this->validator( $request->all(), $this->validatorRules, $customRules, $messages );

        if ( $validator->fails() ) {
            // error
            $this->result['status'] = 'error';
            $this->result['response']['errors'] = $validator->errors();

        } else {
            $this->result['status'] = 'success';
        }

        return $this->result();
    }

    // register - auth user
    public function sendSmsCode(Request $request, bool $sendCode = true, $msg = NULL, $len = 6){ //

        $phone = $request->input('phone');

        $code = rand(0,9) . rand(0,9) . rand(0,9) . rand(0,9);

        if (!OTPAttemptsHelper::isAvailableToSendOTP(correct_phone($phone))) {
            return BaseService::handleError([__('mobile/v3/otp.attempts_timeout')]);
        }


        $sms_text = "Kod: " . $code . ". resusnasiya.uz platformasiga xush kelibsiz! Tel: " . callCenterNumber(2);

        $hashedCode = Hash::make( $phone . $code);

        if ($sendCode) {
            // $sms_text = $code;
            [$result, $http_code] = SmsHelper::sendSms($phone, $sms_text);
            Log::info($result);

            if (($http_code === 200) || ($result === SmsHelper::SMS_SEND_SUCCESS)) {
                $otpService = new OtpService($phone,$code);
                $otpService->save_record();
                Redis::set($phone, $hashedCode,'EX', '60');
                return ['status'=>'success','hash'=>$hashedCode];
            }
        }

        if ( !$sendCode ) {
            return ['code'=>$code, 'hashed' => $hashedCode];
        }

        return ['status'=>'error','info'=>'sms_not_sended'];

    }



}
