<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class KatmScoring extends Model
{

    public function getUpdatedAtAttribute()
    {
        return Carbon::parse($this->attributes['updated_at'])->format('d.m.Y H:i:s');
    }

    public function buyer()
    {
        return $this->belongsTo(Buyer::class, 'user_id');
    }

    public function cardScoring()
    {
        return $this->hasOne(CardScoringLog::class, 'user_id', 'user_id');
    }

}
