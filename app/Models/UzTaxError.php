<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UzTaxError extends Model
{
    protected $table = 'uz_tax_errors';

    protected $fillable = ['error_code'];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function orderProducts()
    {
        return $this->hasManyThrough(OrderProduct::class, Contract::class, 'id', 'order_id', 'contract_id', 'order_id');
    }
}
