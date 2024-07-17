<?php

namespace App\Jobs\KatmReports;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

abstract class BaseKatmReportJob implements ShouldQueue
{

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const LIFETIME = 2;
    private const KATM_REPORT = "katm_report";

    protected function rememberContractInCache(int $contractID): void
    {
        $ttl = Carbon::now()->addMinutes(self::LIFETIME)->diffInSeconds();
        Redis::set(self::KATM_REPORT . ":" . $contractID, "1");
        Redis::expire(self::KATM_REPORT . ":" . $contractID, $ttl);
    }

    protected function forgetContractFromCache(int $contractID): void
    {
        Redis::del(self::KATM_REPORT . ":" . $contractID);
    }

    protected function getContractsFromCache(): array
    {
        $arr = Redis::keys(self::KATM_REPORT . ":*");
        return array_map(function ($key) {
            return substr($key, strpos($key, ":") + 1);
        }, $arr);
    }

}
