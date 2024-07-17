<?php

namespace App\Models\V3;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 */
class KatmV3Response extends Model
{
    protected $fillable = [
        'user_id',
        'claim_id',
        'params',
    ];

    public function setParamsAttribute(array $value)
    {
        $this->attributes['params'] = json_encode($value);
    }

    public function getParamsAttribute($value)
    {
        return $this->attributes['params'] = json_decode($value);
    }
}
