<?php

namespace App\Classes\Informer;

use App\Classes\Informer\Interfaces\IOSendMessage;

class TelegramMessage extends Message
{

    protected $sender;

    public function __construct(IOSendMessage $sender, $text, $title = '', $tags = [])
    {
        $this->sender = $sender;
        parent::__construct($text, $title, $tags);
    }

    public function send()
    {
        $this->sender->line('<b>' . $this->title . '</b>');
        $this->sender->line('');
        $this->sender->line($this->text);
        $this->sender->line('');
        $tags = [];
        foreach ($this->tags as $tag) {
            $tags[] = '#' . $tag;
        }
        $this->sender->line(implode(' ', $tags));
        $this->sender->send();
    }

}
