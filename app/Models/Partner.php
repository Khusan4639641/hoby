<?php

namespace App\Models;

use Illuminate\Support\Carbon;

class Partner extends User
{
    public function getDateAttribute() {
        return Carbon::parse( $this->attributes['created_at'] )->format( 'd.m.Y' );
    }

    public function company(){
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function orders(){
        return $this->hasMany(Order::class, 'supplier_id');
    }

    public function settings(){
        return $this->hasOne(PartnerSetting::class, 'company_id', 'company_id');
    }
    public function user() {
        return $this->hasOne(User::class, 'id', 'id');
    }

}
