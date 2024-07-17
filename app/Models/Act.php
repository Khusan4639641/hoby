<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Act extends Model
{

    protected $fillable = [
        'user_id',
        'initiation_date',
        'number',
        'observer_name',
        'observer_phone',
        'contract_id',
    ];

}
