<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ocr extends Model
{
    protected $table = 'ocr';

    protected $timestamp = false;

    protected $fillable = [
        'user_id', 'response'
    ];
}
