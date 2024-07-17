<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AvailablePeriod extends Model
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    protected $fillable = [
        'period',
        'period_months',
        'title_ru',
        'title_uz',
        'status',
        'reverse_calc',
    ];
}
