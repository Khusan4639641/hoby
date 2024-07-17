<?php

namespace App\Classes\Informer;

use App\Classes\Informer\Interfaces\IOLineMessage;

class ConsoleMessage
{

    const DEFAULT_STYLE = 'line';
    const INFO_STYLE = 'info';

    protected $sender;

    private $data = [];

    public function __construct(IOLineMessage $sender)
    {
        $this->sender = $sender;
    }

    private function addLine(string $text, string $style = '')
    {
        $item = [
            'style' => $style,
            'text' => $text,
        ];
        $this->data[] = $item;
    }

    public function addTitle(string $text)
    {
        $this->addLine('');
        $this->addLine($text, static::INFO_STYLE);
    }

    public function addValueMessage(string $text, int $value)
    {
        $this->addLine($text . ': ' . $value, static::DEFAULT_STYLE);
    }

    public function send()
    {
        foreach ($this->data as $item) {
            $text = $item['text'];
            switch ($item['style']) {
                case static::DEFAULT_STYLE:
                    $this->sender->line($text);
                    break;
                case static::INFO_STYLE:
                    $this->sender->info($text);
                    break;
                default:
                    $this->sender->line('');
                break;
            }
        }
    }

}
