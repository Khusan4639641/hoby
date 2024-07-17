<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Discount extends Model
{
    public function setDatetimeStartAttribute($value){
        $this->attributes['datetime_start'] = date( 'Y-m-d H:i:s', strtotime( $value ) );
    }

    public function setDatetimeEndAttribute($value){
        $this->attributes['datetime_end'] = date( 'Y-m-d H:i:s', strtotime( $value ) );
    }

    public function getDateStartAttribute(  ) {
        return Carbon::parse( $this->attributes['datetime_start'] )->format( 'd.m.Y' );
    }

    public function getTimeStartAttribute(  ) {
        return Carbon::parse( $this->attributes['datetime_start'] )->format( 'H:i' );
    }

    public function getDateEndAttribute(  ) {
        return Carbon::parse( $this->attributes['datetime_end'] )->format( 'd.m.Y' );
    }

    public function getTimeEndAttribute(  ) {
        return  Carbon::parse( $this->attributes['datetime_end'] )->format( 'H:i' );
    }

    public function images(){
        return $this->hasMany(File::class, 'element_id')->where('model', 'discount')->where('type', 'image');
    }

    public function language($code) {
        return $this->hasOne(DiscountLanguage::class)->whereLanguageCode($code);
    }

    public function languages(){
        return $this->hasMany(DiscountLanguage::class, 'discount_id');
    }

}
