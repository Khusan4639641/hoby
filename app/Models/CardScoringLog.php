<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardScoringLog extends Model
{

    const RESPONSE_test = 1;
    const RESPONSE_UNIVERSAL = 2;

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function istestResponse(): bool
    {
        return $this->response_type == self::RESPONSE_test;
    }

    public function isUniversalResponse(): bool
    {
        return $this->response_type == self::RESPONSE_UNIVERSAL;
    }

}
