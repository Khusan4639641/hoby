<?php

namespace App\Models\V3;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'cbu_id',
        'iiv_id',
        'postal_id'
    ];

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }
}
