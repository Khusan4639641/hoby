<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardPassportScoring extends Model
{
    protected $fillable = [
        'user_id', 'card_id', 'simplify'
    ];
}
