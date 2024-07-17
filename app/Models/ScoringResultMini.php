<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class ScoringResultMini extends ScoringResult
{

    protected $attributes = [
        'is_katm_auto' => true,
        'type' => ScoringResult::TYPE_MINI,
    ];

    protected static function booted()
    {
        static::addGlobalScope('type', function (Builder $builder) {
            $builder->where('type', ScoringResult::TYPE_MINI);
        });
    }

    public function scopeTyped($query)
    {
        return $query->where('type', self::TYPE_MINI);
    }

    public function isExecuted(): bool
    {
        return $this->final_state === self::STATE_USER_INFO_SUCCESS;
    }

}
