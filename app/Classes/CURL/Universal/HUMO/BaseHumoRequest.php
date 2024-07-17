<?php

namespace App\Classes\CURL\Universal\HUMO;

use App\Classes\CURL\Universal\BaseUniversalRequest;
use App\Facades\OldCrypt;

abstract class BaseHumoRequest extends BaseUniversalRequest
{

    public function __construct()
    {
        $this->baseUrl = config('test.universal_url_humo');
        $this->token = OldCrypt::decryptString(config('test.universal_token_humo'));
    }

}
