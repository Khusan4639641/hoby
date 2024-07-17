<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KatmMib extends Model
{

    public $table = 'katm_mib';

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class, 'user_id', 'id');
    }

}
