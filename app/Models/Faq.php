<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $table = 'faq';

    public function language($code) {
        return $this->hasOne(FaqLanguage::class)->whereLanguageCode($code);
    }

    public function languages(){
        return $this->hasMany(FaqLanguage::class, 'faq_id');
    }
}
