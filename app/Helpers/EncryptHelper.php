<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class EncryptHelper
{

    //TODO: Увеличить битность шифрования при заливке на прод

    public static function encryptData($data, $key = '')
    {
        if ($data == '') {
            return '';
        }

        if ($key == '') {
            $key = Storage::disk('rsa')->get("id_rsa.pub");
        }
        openssl_public_encrypt($data, $crypted, $key);

        return base64_encode($crypted);
    }

    public static function decryptData($data)
    {
        if ($data == '') {
            return '';
        }

        $md5Key = md5($data);
        $result = Redis::get('_decrypted_' . $md5Key,);
        if ($result) {
            return $result;
        }

        $key = Storage::disk('rsa')->get("id_rsa");
        openssl_private_decrypt(base64_decode($data), $decrypted, $key);
        Redis::set('_decrypted_' . $md5Key, $decrypted, 'EX', 432000);

        return $decrypted;
    }
}
