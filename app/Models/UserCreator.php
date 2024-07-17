<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCreator extends Model
{
    protected $fillable = ['user_id','creator_id','ip_address'];
}
