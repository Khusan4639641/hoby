<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExecutiveWriting extends Model
{
    protected $fillable = [
        'user_id',
        'contract_id',
        'registration_number',
    ];
}
