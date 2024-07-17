<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RoyxatCredits extends Model
{

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class, 'user_id', 'id');
    }

}
