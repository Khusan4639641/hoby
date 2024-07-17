<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Unit extends Model
{
    protected $table = 'units';

    protected $fillable = [
        'title'
    ];

    public function languages() {
        return $this->hasMany( UnitLanguage::class, 'unit_id' );
    }

    public function language($code = null) {
        if(is_null($code))
            $code = app()->getLocale();
        return $result = $this->hasOne(UnitLanguage::class, 'unit_id')->whereLanguageCode($code);
    }
}
