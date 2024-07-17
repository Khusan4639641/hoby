<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayService extends Model
{
    public function getImgAttribute () {
        return asset('images/partners/'.$this->attributes['img']);
    }
}
