<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model {
    protected $table = 'cart';

    public function product()
    {
        return $this->hasOne(CatalogProduct::class, 'id');
    }

    public function settings()
    {
        return $this->hasOne(CartSetting::class, 'cart_id', 'cart_id');
    }
}
