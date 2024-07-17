<?php

namespace App\Helpers;

use LaravelQRCode\Facades\QRCode;

class QRCodeHelper
{
    public static function url($url){
        return QRCode::url($url)->png();
    }

    public static function text($text){
        return QRCode::text($text)->png();
    }
}
