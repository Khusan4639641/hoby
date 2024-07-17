<?php

namespace App\Logging;

use Illuminate\Support\Str;
use Monolog\Formatter\LineFormatter;

class UniversalFormatter
{
    /**
     * Настроить переданный экземпляр регистратора.
     *
     * @param \Illuminate\Log\Logger $logger
     * @return void
     */
    public function __invoke($logger)
    {
        $transaction = Str::random(12);
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new LineFormatter(
                "[%datetime%] (ID: " . $transaction . ") %channel%.%level_name%: %message% %context% %extra%\n",
                "Y-m-d H:i:s"
            ));
        }
    }
}
