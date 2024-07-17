<?php

namespace App\Classes\Informer;

use App\Classes\Informer\Interfaces\IOSendMessage;
use App\Classes\Informer\Interfaces\MessageData;
use Carbon\Carbon;

class DailyTelegramMessage extends TelegramMessage
{

    private $dividingLine = "---------------------------------------------";

    public function __construct(IOSendMessage $sender, Carbon $date, string $title = '')
    {
        $title = $title != '' ? ' ' . $title . '.' : '';
        parent::__construct($sender, $this->text, "\xF0\x9F\x93\x85" . $title . ' Ежедневный отчёт за ' . $date->format('d.m.Y') . ' ' . "\xF0\x9F\x93\x85", ['dailyreport']);
    }

    public function addReport(MessageData $message)
    {
        $this->text = ltrim($this->text, PHP_EOL . $this->dividingLine) . PHP_EOL . $this->dividingLine . PHP_EOL . $message->getText();
    }

}
