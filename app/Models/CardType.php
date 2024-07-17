<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CardType extends Model
{
    protected $fillable = [
        'name',
        'prefix',
        'type_id',
        'description',
    ];
}
