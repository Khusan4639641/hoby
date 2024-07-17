<?php

namespace App\Classes\CURL\Universal\UZCARD;

use App\Classes\CURL\Universal\BaseUniversalRequest;
use App\Facades\OldCrypt;

abstract class BaseUzcardRequest extends BaseUniversalRequest
{

    public function __construct()
    {
        $this->baseUrl = config('test.universal_url_uzcard');
        $this->token = OldCrypt::decryptString(config('test.universal_token_uzcard'));
    }

}
