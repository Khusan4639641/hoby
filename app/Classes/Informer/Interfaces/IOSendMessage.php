<?php

namespace App\Classes\Informer\Interfaces;

interface IOSendMessage
{

    public function line(string $message);

    public function send();

}
