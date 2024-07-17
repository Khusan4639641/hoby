<?php

namespace App\Models;

use Illuminate\Support\Carbon;

class Saller extends User
{

    public function buyerSettings()
    {
        return $this->hasOne(BuyerSetting::class, 'user_id', 'id');
    }

    public function getDateAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->format('d.m.Y');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function companyEmployer()
    {
        return $this->belongsTo(Company::class, 'seller_company_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'id');
    }

    public function personals()
    {
        return $this->hasOne(BuyerPersonal::class, 'user_id', 'id');
    }

    public function passport()
    {
        return $this->hasOne(File::class, 'element_id', 'id')->where('model', 'saller')->where('type', 'passport');
    }

    public function passportAddress()
    {
        return $this->hasOne(File::class, 'element_id', 'id')->where('model', 'saller')->where('type', 'passport_address');
    }

    public function latestPassport()
    {
        return $this->hasOne(File::class, 'element_id', 'id')->where('model', 'saller')->where('type', 'passport')->latest();
    }

    public function latestPassportAddress()
    {
        return $this->hasOne(File::class, 'element_id', 'id')->where('model', 'saller')->where('type', 'passport_address')->latest();
    }

    public function getBonusAccountAttribute()
    {
        return $this->buyerSettings->zcoin;
    }

    public function addToBonusAccount(float $amount)
    {
        $this->buyerSettings->zcoin += $amount;
        return $this->buyerSettings->save();
    }

}
