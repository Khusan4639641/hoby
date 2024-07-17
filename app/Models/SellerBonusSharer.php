<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerBonusSharer extends Model {

    protected $fillable = [
        'user_id',
        'sharer_id',
        'percent',
    ];
}
