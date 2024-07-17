<?php

namespace App;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNibbd extends Model
{
    protected $table = 'user_nibbds';

    protected $fillable = ['code','user_id'];

    public function user() : BelongsTo
    {
        return $this->belongsTo(User::class,'user_id','id');
    }
}
