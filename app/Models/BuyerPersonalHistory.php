<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuyerPersonalHistory extends Model
{
    protected $fillable = [
        'passport_number',
        'passport_date_issue',
        'passport_issued_by',
        'passport_expire_date',
        'passport_type',
    ];
}
