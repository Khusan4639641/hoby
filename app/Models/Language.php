<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort', 'asc')->get();
    }
}
