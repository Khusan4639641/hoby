<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PassportIssuerRegion extends Model
{
    protected $fillable = [
        'issuer_id',
        'name_uz',
        'name_ru',
        'region',
        'local_region',
    ];
}
