<?php

namespace App\Helpers\V3;

use App\Models\User;
use App\Models\V3\OtpEnterCodeAttempts;
use Illuminate\Support\Facades\Redis;

class OTPAttemptsHelper
{

    const MAX_ATTEMPTS_VALUE = 5;

    const OTP_ERRORS_CODES = [
        1 => 'неверный ввод кода',
        2 => 'Переизбыток попыток',
        3 => 'Тайм аут',
        4 => 'Запись не найдена'
    ];


    public static function checkOtpCode($phone, bool $is_true_code = false): array
    {
        $record = OtpEnterCodeAttempts::where('phone', $phone)->first();


        if (!isset($record)) {
            return ['error' => true, 'message' => [__('mobile/v3/otp.record_not_found')], 'errorCode' => 4];
        }

        if ($record->attempts >= self::MAX_ATTEMPTS_VALUE) {
            return ['error' => true, 'message' => [__('mobile/v3/otp.attempts_limit')], 'errorCode' => 2];
        }

        if ($is_true_code) {
            //Если клиент не уложился в течение минуты после отправки  otp
            if (time() > ($record->updated_at->timestamp + 1 * 60)) {
                return ['error' => true, 'message' => [__('mobile/v3/otp.minute_timeout')], 'errorCode' => 3];
            }
            $record->update(['attempts' => 0]); //Сбрасываем попытки клиенту - клиент верно все сделал
            return ['error' => false];
        }

        $record->update(['attempts' => ++$record->attempts]); // Добавляем в счетчик попытки
        return ['error' => true, 'message' => [__('mobile/v3/otp.incorrect_code')], 'errorCode' => 1];
    }


    public static function isAvailableToSendOTP(string $phone): bool
    {
        if (str_contains('+', $phone)) {
            $phone = str_replace('+', '', $phone);
        }
        if (Redis::get($phone)) {
            return false;
        }

        $user = User::where('phone', $phone)->first();
        if (isset($user)) {
            $record = OtpEnterCodeAttempts::where('user_id', $user->id)->first();
        } else {
            $record = OtpEnterCodeAttempts::where('phone', $phone)->first();
        }


        if (!isset($record)) {
            return true;
        }

        if ($record->attempts > self::MAX_ATTEMPTS_VALUE && ($record->updated_at->timestamp + 60 * 2) < time()) {
            return true;
        }
        if ($record->attempts > self::MAX_ATTEMPTS_VALUE) {
            return false;
        }

        return true;
    }

    public static function generateCode($len = 1): int
    {
        $otp = '';
        while ($len) {
            $otp .= rand(1, 9);
            $len--;
        }
        return (int)$otp;
    }
}
