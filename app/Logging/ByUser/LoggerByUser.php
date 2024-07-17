<?php

namespace App\Logging\ByUser;

use App\Models\User;
use Monolog\Logger;

class LoggerByUser extends Logger
{

    public function __construct(User $user, string $folder = 'user', string $alias = 'data')
    {
        $date = date('Ymd');
        $userID = $user->id;
        $url = storage_path() . '/logs/' . $folder . '/' . $date . '/user_' . $userID . '_' . $alias . '.log';
        $handler = new LogScoringHandler($url, '');
        parent::__construct('laravel');
        $this->pushHandler($handler);
    }

}
