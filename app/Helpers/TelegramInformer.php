<?php

namespace App\Helpers;

use App\Classes\Informer\Interfaces\IOSendMessage;

class TelegramInformer implements IOSendMessage
{

    private const BOT_API = 'https://api.telegram.org/bot';

    protected $apiKey = '';
    protected $chat = '';
    protected $message = '';
    protected $silence = false;

    public function __construct($apiKey = '', $chat = '')
    {
        $this->token = env('TELEGRAM_BOT_TOKEN');
        if ($apiKey != '') {
            $this->apiKey = $apiKey;
        }
        $this->chat = env('TELEGRAM_CHAT_ID');
        if ($chat != '') {
            $this->chat = $chat;
        }
    }

    public function silence()
    {
        $this->silence = true;
    }

    public function line($message)
    {
        $this->message = $this->message . PHP_EOL . $message;
    }

    public function info($message)
    {
        $this->message = $message;
    }

    public function send()
    {
        $ch = curl_init();
        $url = self::BOT_API . $this->apiKey . '/SendMessage';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'text' => $this->message,
            'chat_id' => $this->chat,
            'parse_mode' => 'HTML',
            'disable_notification' => $this->silence,
        ]));

        $result = curl_exec($ch);
        if (!is_string($result)) {
            throw new \Exception('Telegram API error. Description: No response');
        }
        $result = json_decode($result, true);

        if ($result['ok'] === false) {
            throw new \Exception('Telegram API error. Description: ' . $result['description']);
        }
    }

}
