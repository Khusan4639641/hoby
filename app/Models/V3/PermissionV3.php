<?php

namespace App\Models\V3;

use Illuminate\Database\Eloquent\Model;

class PermissionV3 extends Model
{
    protected $table = 'permissions';
    protected $fillable = [
        'name',
        'display_name',
        'route_name',
    ];
    //
}
