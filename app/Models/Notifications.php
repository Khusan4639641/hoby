<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{
    protected $fillable = ['id','type','notifiable_type','notifiable_id','data','hash','read_at'];
}
