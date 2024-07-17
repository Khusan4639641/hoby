<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipping extends Model
{
    protected $appends = ['name'];

    public function getNameAttribute() {
        return __('shipping/'.mb_strtolower($this->attributes['code']).'.name');
    }
}
