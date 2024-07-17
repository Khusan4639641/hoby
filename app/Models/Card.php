<?php

namespace App\Models;

use App\Helpers\EncryptHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Card extends Model
{
    protected $fillable = ['user_id', 'card_name', 'card_number', 'card_valid_date', 'phone',
        'token', 'type', 'guid', 'status', 'is_main', 'kyc_status', 'bean', 'processing_type', 'token_payment',
        'is_processing_active'];

    protected $appends = ['public_number', 'public_card_name'];

    const CARD_ACTIVE = 1;
    const CARD_INACTIVE = 0;
    const CARD_DELETED = 2;
    const CARD_LENGTH = 16;
    const CARD_HIDDEN = 0;
    const CARD_NOT_HIDDEN = 1;


    public function getNumberDecodedAttribute()
    {
        return EncryptHelper::decryptData($this->attributes['card_number']);
    }

    public function getExpireDecodedAttribute()
    {
        return EncryptHelper::decryptData($this->attributes['card_valid_date']);
    }

    public function getExpireDecodedToMonthYearAttribute()
    {
        $expireDecoded = EncryptHelper::decryptData($this->attributes['card_valid_date']);
        return Str::substr($expireDecoded, 2, 2) . Str::substr($expireDecoded, 0, 2);
    }

    public function getPublicNumberAttribute()
    {
        $number = EncryptHelper::decryptData($this->attributes['card_number']);
        return '**** **** **** ' . substr($number, -4);
    }

    public function getPublicCardNameAttribute()
    {
        return $this->attributes['card_name'];
    }

    public function getTypeAttribute()
    {
        return EncryptHelper::decryptData($this->attributes['type']);
    }

    public function getPhoneAttribute()
    {
        $phone = $this->attributes['phone'];
        return strlen($phone) === 12 ? $phone : EncryptHelper::decryptData($phone);
    }

    public function scoring()
    {
        return $this->hasManyThrough(CardScoringLog::class, CardScoring::class, 'user_card_id', 'card_scoring_id');
    }

}
