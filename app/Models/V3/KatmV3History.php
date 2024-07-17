<?php

namespace App\Models\V3;

use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $array)
 */
class KatmV3History extends Model
{
    protected $fillable = [
        'user_id',
        'claim_id',
        'params',
        'response',
        'report_code',
        'is_complete',
        'token',
    ];

    public function setParamsAttribute(array $value)
    {
        $this->attributes['params'] = json_encode($value);
    }

    public function getParamsAttribute($value)
    {
        return $this->attributes['params'] = json_decode($value);
    }

    public function setResponseAttribute(array $value)
    {
        $this->attributes['response'] = json_encode($value);
    }

    public function getResponseAttribute($value)
    {
        return $this->attributes['response'] = json_decode($value);
    }

}
