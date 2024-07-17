<?php

namespace App\Services\MFO;

use App\Helpers\V3\OTPAttemptsHelper;
use App\Models\Buyer;
use App\Models\Contract;
use App\Models\User;
use App\Services\API\V3\BaseService;
use App\Services\API\V3\LoginService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;

class MFOAuthService extends BaseService
{

    public function login(int $contract_id):void
    {
        $user = self::CheckClientCredentials($contract_id);

        $phone = correct_phone($user->phone);

        LoginService::sendSmsCode($phone);
    }

    public function verifyLogin(int $contract_id, string $code): array
    {
        $user = self::CheckClientCredentials($contract_id);

        $phone = correct_phone($user->phone);


        $hashedCode = Redis::get($phone);
        $result = Hash::check($phone . $code, $hashedCode);

        if (!$result) {
            return self::handleError([__('mobile/v3/otp.incorrect_code')]);
        }

        $otpCodeResponse = OTPAttemptsHelper::checkOtpCode($phone, $result);

        if ($otpCodeResponse['error']) {
            if($otpCodeResponse['errorCode']) {
                Redis::del($phone);
            }
            return self::handleError($otpCodeResponse['message'], 'error', 400, $otpCodeResponse['errorCode']);
        }

        $data['user_status'] = $user->status;
        $data['user_id'] = $user->id;
        $data['api_token'] = $user->api_token;

        return $data;

    }

    private static function CheckClientCredentials(int $contract_id)
    {

        $contract = Contract::where('id', $contract_id)->first();

        if (!isset($contract)) {
            return self::handleError([__('api.contract_not_found')]);
        }

        $user_id = $contract->user_id;

        $user = Buyer::where('id', $user_id)->first();

        if (!isset($user)) {
            return self::handleError([__('api.buyer_not_found')]);
        }

        if ($user->status === User::KYC_STATUS_BLOCKED) {
            return self::handleError([__('api.err_black_list')]);
        }


        return $user;
    }

}
