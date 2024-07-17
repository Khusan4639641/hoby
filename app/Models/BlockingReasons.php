<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockingReasons extends Model
{
    protected $table    = 'blocking_reasons';

    protected $fillable = ['id', 'name', 'position'];

    protected $hidden   = ['created_at', 'updated_at'];
}
