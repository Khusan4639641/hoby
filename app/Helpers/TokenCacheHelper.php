<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;

class TokenCacheHelper
{

    static private string $hash = ':token';

    static private string $owner = ':ml';

    public static function get(): string
    {
        return Redis::hGet(self::$hash . ":" . self::$owner, "token");
    }

    public static function exists(): bool
    {
        return (bool)Redis::hexists(self::$hash . ":" . self::$owner, "token");
    }

    public static function update(string $token): void
    {
        $ttl = Carbon::now()->diffInSeconds(Carbon::now()->endOfDay(), false);
        Redis::hSet(self::$hash . ":" . self::$owner, "token", $token);
        Redis::expire(self::$hash . ":" . self::$owner, $ttl);
    }

}
