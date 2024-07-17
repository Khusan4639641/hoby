<?php
namespace App\Traits;

use App\Helpers\SmsHelper;
use App\Helpers\V3\OTPAttemptsHelper;
use App\Models\User;
use App\Services\API\V3\BaseService;
use App\Services\Mobile\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

trait SmsTrait {
    /**
     * @OA\Post(
     *      path="/login/send-sms-code",
     *      operationId="send sms",
     *      tags={"Authorization"},
     *      summary="SMS code user by phone",
     *      description="Return code",
     *      @OA\Parameter(
     *          name="phone",
     *          description="Buyer phone",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
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
     * Send sms code for check operation
     *
     * @param Request $request
     * @param bool $sendCode
     * @return array|string
     */
    public function sendSmsCode(Request $request, bool $sendCode = true, $msg = null, $len = 6){

        $phone = correct_phone($request->input('phone'));

        if($user = User::where('phone',$phone)->where('status',User::KYC_STATUS_BLOCKED)->first()){
            return ['status'=>'error','info'=>'User is blocked'];
        }


        $card_phone = $request->card_phone ?? false; //номер смс информирования

        if (!OTPAttemptsHelper::isAvailableToSendOTP(correct_phone($phone))) {
            return BaseService::handleError([__('mobile/v3/otp.attempts_timeout')]);
        }

        $code = OTPAttemptsHelper::generateCode($len);

        if ($sendCode) {
            if (!$msg) {
                $msg = "Kod: {$code}. resusnasiya.uz Platformasiga xush kelibsiz! Tel: " . callCenterNumber(2);
            } else {
                $msg = str_replace(':code', $code, $msg);
            }
        }

        $hashedCode = Hash::make( $phone . $code);

        if ($sendCode) {
            $user_phone = $card_phone ?: $phone;  // 23.08.2021
            [$result, $http_code] = SmsHelper::sendSms($user_phone, $msg);
            Log::info($result);

            // changed here == to !=
            if (($http_code === 200) || ($result === SmsHelper::SMS_SEND_SUCCESS)) {
               try {

                   $otpService = new OtpService($phone,$code);
                   $otpService->save_record();
                   Redis::set($phone, $hashedCode,'EX', '60');
               }catch (\Exception $e){
                   dd($e);
               }
                return ['status'=>'success','hash'=>$hashedCode];
            }
        }

        if ( !$sendCode ) {
            return ['code'=>$code, 'hashed' => $hashedCode];
        }

        return ['status'=>'error','info'=>'sms_not_sended'];

    }
    /**
     * Verify sms code
     *
     * @param Request $request
     * @return mixed
     */
    public function checkSmsCode(Request $request) {

        $code = $request->input('code');
        if (!$phone = $request->input('phone')) {
            $user = Auth::user();
            $phone = correct_phone($user->phone);
        }

        if ($request->has('hashedCode'))
            $hashedCode = $request->input('hashedCode');

        else $hashedCode = Redis::get($phone);

        $result = Hash::check($phone . $code, $hashedCode);  // string given

        if ($result) {
            $this->result['status'] = 'success';
            if ($user = User::where('phone', $request->phone)->first()) {
                $this->result['is_seller'] = $user->is_saller; // если пользвоатель продавец

                $this->result['data'] = [
                    'user_id' => $user->id,
                    'access_token' => $user->api_token,
                    'user_status' => $user->status,
                ];

            }
            Redis::del($phone);
        } else {
            $this->result['status'] = 'error';
            $this->message('danger', __('auth.error_code_wrong'));
        }
//        dd($request->all());
        return $this->result();
    }
}
