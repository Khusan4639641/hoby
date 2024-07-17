<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ScoringCacheHelper
{
    static string $hash = ':scoring';

    public static function get(int $user_id, string $key): array
    {
        Log::channel('scoring_cache')->info("Получение данных от REDIS : " . self::$hash . ":" . $user_id . " | " . $key);
        return json_decode(Redis::hGet(self::$hash . ":" . $user_id, $key,), true);
    }

    public static function set(int $user_id, string $key, array $value = []): void
    {
        Log::channel('scoring_cache')->info("Запись данных в REDIS : " . self::$hash . ":" . $user_id . " | " . $key, $value);
        // количество дней до конца месяца * 24 часа * 60 минут * 60 секунд
        $ttl = Carbon::now()->diffInDays(Carbon::now()->endOfMonth()->addDay(), false) * 24 * 60 * 60;
        Redis::hSet(self::$hash . ":" . $user_id, $key, json_encode($value));
        Redis::expire(self::$hash . ":" . $user_id, $ttl);
    }

    public static function exists(int $user_id, string $key): bool
    {
        Log::channel('scoring_cache')->info("Проверка наличия данных в REDIS : " . self::$hash . ":" . $user_id . " | " . $key);
        return (bool)Redis::hexists(self::$hash . ":" . $user_id, $key,);
    }

    public static function keys(int $user_id): array
    {
        return Redis::hKeys(self::$hash . ":" . $user_id);
    }

    public static function remove(int $user_id)
    {
        return Redis::del(self::$hash . ":" . $user_id);
    }

}
