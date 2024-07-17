<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collector extends Model
{

    use SoftDeletes;
    
    protected $fillable = [
        'user_id', 'chat_id', 'balance',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['user', 'contracts'];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function katm_regions()
    {
        return $this->belongsToMany('App\Models\KatmRegion')->withTimestamps();
    }

    public function contracts()
    {
        return $this->belongsToMany('App\Models\Contract')->withPivot('id')->withTimestamps();
    }
}
