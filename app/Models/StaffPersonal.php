<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffPersonal extends Model
{
    const STATUS_WORKS = 1;
    const STATUS_NO_LONGER_WORKS = 0;

    protected $fillable = ['fullname', 'pinfl'];
}
