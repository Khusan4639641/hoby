<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UnitLanguage extends Model {

    protected $fillable = [
        'language_code',
        'unit_id',
        'title',
    ];
}
