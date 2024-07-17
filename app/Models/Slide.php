<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Slide extends Model
{
    public function slider(){
        return $this->belongsTo(Slider::class, 'slider_id');
    }


    public function image(){
        return $this->hasOne(File::class, 'element_id')->where('model', 'slide')->where('type', 'image');
    }
}
