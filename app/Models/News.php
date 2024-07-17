<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class News extends Model
{
    protected $table = 'news';

    public function setDateAttribute($value){
        $this->attributes['date'] = date( 'Y-m-d', strtotime( $value ) );
    }

    public function getDateAttribute() {
        return Carbon::parse( $this->attributes['date'] )->format( 'd.m.Y' );
    }

    public function getDateFormatAttribute(){
        return Carbon::parse( $this->attributes['date'] )->format( 'd F Y' );
    }
/*
    public function images(){
        return $this->hasMany(File::class, 'element_id')->where('model', 'news')->where('type', 'image');
    }*/

    public function language($code) {
        return $this->hasOne(NewsLanguage::class)->whereLanguageCode($code);
    }

    public function languages(){
        return $this->hasMany(NewsLanguage::class, 'news_id');
    }
}
