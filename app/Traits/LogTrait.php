<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait LogTrait
{

    private $channel = 'laravel';

    public function logInfo($message, $context)
    {
        Log::channel($this->channel)->info($message, $context);
    }

    public function logError($message, $context)
    {
        Log::channel($this->channel)->error($message, $context);
    }

    /*
     * @todo deprecated
     */
    static public function sLogInfo($message, $context)
    {
        Log::channel(self::LOG_CHANNEL)->info($message, $context);
    }

    /*
     * @todo deprecated
     */
    static public function sLogError($message, $context)
    {
        Log::channel(self::LOG_CHANNEL)->error($message, $context);
    }

}
