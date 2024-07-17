<?php

namespace App\Classes\Informer;

use App\Classes\Informer\Interfaces\IOSendMessage;
use App\Classes\Informer\Interfaces\MessageData;

class WarningTelegramMessage extends TelegramMessage implements MessageData
{

    private $up = "\xE2\xAC\x86";
    private $down = "\xE2\xAC\x87";
    private $stable = "\xF0\x9F\x91\x8C";

    public function __construct(IOSendMessage $sender, string $message, int $prevValue, int $nextValue)
    {
        $text = $message . ': <b>' . $nextValue . '</b>';
        if ($prevValue == $nextValue) {
            $text .= PHP_EOL . $this->stable . ' <b>Стабильно. Без изменений.</b>';
        } else if ($prevValue > $nextValue) {
            $text .= PHP_EOL . $this->down . ' <i>Спад на <b>' . ($prevValue - $nextValue) . '</b> позиций | Было: <b>' . $prevValue . '</b> | Стало: <b>' . $nextValue . '</b></i>';
        } else {
            $text .= PHP_EOL . $this->up . ' <i>Прирост на <b>' . ($nextValue - $prevValue) . '</b> позиций | Было: <b>' . $prevValue . '</b> | Стало: <b>' . $nextValue . '</b></i>';
        }
        parent::__construct($sender, $text, "\xE2\x9A\xA0" . ' Внимание! ' . "\xE2\x9A\xA0", ['warning']);
    }

    public function getText(): string
    {
        return $this->text;
    }

}
