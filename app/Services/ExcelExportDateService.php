<?php

namespace App\Services;

use Carbon\Carbon;

class ExcelExportDateService
{
    const SEVEN_DAY = 7;
    const SIX_MONTH = 6;

    const DATE_FROM = 0;
    const DATE_TO = 1;

    public static function getDates(string $type, array $dates = [])
    {
        switch ($type) {
            case 'custom':
                return [
                    Carbon::createFromFormat('Y-m-d', $dates[self::DATE_FROM])->startOfDay()->toDateTimeString(),
                    Carbon::createFromFormat('Y-m-d', $dates[self::DATE_TO])->endOfDay()->toDateTimeString(),
                ];
            case 'last_day' :
                return [
                    now()->subDay()->startOfDay()->toDateTimeString(),
                    now()->endOfDay()->toDateTimeString()
                ];
            case 'last_week':
                return [
                    now()->subWeek()->startOfDay()->toDateTimeString(),
                    now()->endOfDay()->toDateTimeString()
                ];
            case 'last_7_days':
                return [
                    now()->subDays(self::SEVEN_DAY)->startOfDay()->toDateTimeString(),
                    now()->endOfDay()->toDateTimeString()
                ];
            case 'last_month':
                return [
                    now()->subMonth()->startOfDay()->toDateTimeString(),
                    now()->endOfDay()->toDateTimeString()
                ];
            case 'last_half_year':
                return [
                    now()->subMonths(self::SIX_MONTH)->startOfDay()->toDateTimeString(),
                    now()->endOfDay()->toDateTimeString()
                ];
        }
    }
}
