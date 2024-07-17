<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;


class NdsStopgagHelper
{
    // Из-за того что в некоторых местах в коде захардкожено значение ндс,
    // делаем затычку, чтобы 1 января 2023 года старые документы пересчитывались
    // по старой ставке ндс, а новые по значению из конфига
    public static function getActualNds($date = null): float
    {
        return (float)self::getNds($date);
    }

    public static function getActualNdsValue($date = null): string
    {
        return (string)self::getNds($date) * 100;
    }

    public static function getActualNdsPlusOne($date = null): float
    {
        return (float)self::getNds($date) + 1;
    }

    private static function getNds($date = null): float
    {
        $expiryDate = self::getExpiryDate(false);
        $date       = $date ? Carbon::parse($date) : Carbon::now();

        return $date > $expiryDate ? (float)config('test.nds') : 0.15;
    }

    public static function getExpiryDate(bool $asString = true): string
    {
        $date = Carbon::parse('31.12.2022 23:59:59');
        if ($asString) {
            $date = $date->format('Y-m-d H:i:s');
        }

        return $date;
    }
}
