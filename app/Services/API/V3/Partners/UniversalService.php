<?php

namespace App\Services\API\V3\Partners;

use App\Services\API\V3\BaseService;
use Illuminate\Http\Request;
use Hash;
use Illuminate\Support\Facades\Redis;

class UniversalService extends BaseService
{
    public static function checkSmsCode(string $phone, string $code, string $hashedCode = '')
    {
        if(!$phone || !$code){
            return false;
        }
        $hashedCode = $hashedCode ?? Redis::get($phone);
        $result = Hash::check($phone . $code, $hashedCode);
        if ($result) {
            Redis::del($phone);
            return true;
        }
        return false;
    }
}