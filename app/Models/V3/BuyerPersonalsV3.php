<?php

namespace App\Models\V3;

use Illuminate\Database\Eloquent\Model;
use App\Helpers\EncryptHelper;

class BuyerPersonalsV3 extends Model
{
    protected $table = 'buyer_personals';
    protected $fillable = [
        'birthday',
        'city_birth',
        'work_company',
        'work_phone',
        'passport_number',
        'passport_number_hash',
        'passport_date_issue',
        'passport_issued_by',
        'passport_expire_date',
        'passport_type',
        'home_phone',
        'pinfl',
        'pinfl_hash',
        'pinfl_status',
        'inn',
        'mrz',
        'social_vk',
        'social_facebook',
        'social_linkedin',
        'social_instagram',
        'vendor_link',
        'vendor_link',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];
    protected $casts = [
        'passport_type' => 'integer',
    ];

    public function setBirthdayAttribute(string $value): BuyerPersonalsV3
    {
        $this->attributes['birthday'] = EncryptHelper::encryptData($value);
        return $this;
    }

    public function getBirthdayAttribute(string $value): BuyerPersonalsV3
    {
        $this->attributes['birthday'] = EncryptHelper::decryptData($value);
        return $this;
    }

    public function setPassportNumberAttribute(string $value): BuyerPersonalsV3
    {
        $this->attributes['passport_number'] = EncryptHelper::encryptData($value);
        return $this;
    }

    public function getPassportNumberAttribute(string $value)
    {
        return $this->attributes['passport_number'] = EncryptHelper::decryptData($value);
    }


    public function setPinflAttribute(string $value): BuyerPersonalsV3
    {
        $this->attributes['pinfl'] = EncryptHelper::encryptData($value);
        return $this;
    }

    public function getPinflAttribute(string $value)
    {
        return $this->attributes['pinfl'] = EncryptHelper::decryptData($value);
    }

}
