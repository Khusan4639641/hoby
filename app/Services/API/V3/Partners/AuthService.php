<?php

namespace App\Services\API\V3\Partners;

use App\Models\Company;
use App\Services\API\V3\BaseService;
use Illuminate\Http\Request;
use Validator;
use Hash;
use Illuminate\Support\Facades\Auth;

class AuthService extends BaseService
{
    public static function validateForm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password'   => 'required|string',
            'partner_id' => 'required|numeric|exists:companies,id',
        ]);
        if ($validator->fails()) {
            self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function auth(Request $request)
    {
        $company = Company::with('user')->find($request->partner_id);
        $user = $company->user;
        if(!$user){
            return self::handleError([__('auth.error_user_not_found')]);
        }
        if ($company->status === 0) {
            return self::handleError([__('auth.error_user_inactive')]);
        }
        if (Hash::check($request->password, $user->password)) {
            $user->lang = $request->lang ?? $user->lang;
            if($request->has('system')){
                $user->device_os = $request->system;
                if($request->has('fcm_token')){
                    if($request->system == 'ios'){
                        $user->firebase_token_ios = $request->fcm_token;
                    }
                    if($request->system == 'android'){
                        $user->firebase_token_android = $request->fcm_token;
                    }
                }
            }
            $user->save();
            Auth::login($user);
            $data['user_status'] = $user->status;
            $data['user_id'] = $user->id;
            $data['api_token'] = $user->api_token;
            return self::handleResponse($data);
        }
        return self::handleError([__('auth.error_password_wrong')]);
    }

    public static function checkPassword( Request $request )
    {
        $company = Company::with('user')->find($request->partner_id);
        $user = $company->user;
        if(!$user){
            return self::handleError([__('auth.error_user_not_found')]);
        }
        if (Hash::check($request->password, $user->password)) {
            return self::handleResponse();
        }
        return self::handleError([__('auth.error_password_wrong')]);
    }
}