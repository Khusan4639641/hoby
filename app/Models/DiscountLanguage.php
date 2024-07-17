<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiscountLanguage extends Model
{
    public function images(){
        return $this->hasMany(File::class, 'element_id', 'id')->whereModel('discount-language')->where('type', 'like', '%image%');
    }


    public function image($type){
        return $this->hasOne(File::class, 'element_id', 'id')->whereModel('discount-language')->whereType($type);
    }
}
