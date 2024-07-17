<?php

namespace App\Logging\ByUser;

use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class LogScoringHandler extends StreamHandler
{

    private string $urlTemplate;


    public function __construct(string $urlTemplate, $stream, $level = Logger::DEBUG, bool $bubble = true, ?int $filePermission = null, bool $useLocking = false)
    {
        parent::__construct($stream, $level, $bubble, $filePermission, $useLocking);
        $this->urlTemplate = $urlTemplate;
    }

    protected function write(array $record): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
        }
        $this->stream = null;
        $this->url = $this->urlTemplate;
        parent::write($record);
    }

    protected function getDefaultFormatter(): FormatterInterface
    {
        return new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            "Y-m-d H:i:s",
            true
        );
    }
}
