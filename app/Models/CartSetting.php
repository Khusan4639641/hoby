<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartSetting extends Model {

    public function cart()
    {
        return $this->hasMany(Cart::class, 'cart_id', 'cart_id');
    }
}
