<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuyerGnkSalary extends Model
{
    protected $table = 'buyer_gnk_salaries';

    protected $fillable = [
        'response'
    ];

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class, 'user_id', 'id');
    }

    public function scopeCurMonth($query)
    {
        return $query->whereRaw('MONTH(created_at) = ' . date('m'));
    }

}
