<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MkoReport extends Model
{
    protected $table = 'mko_reports';

    protected $fillable = [
        'mko_id',
        'from',
        'to',
        'dispatch_number',
        'url',
        'is_sent',
        'is_error',
        'report_info'
    ];

    public function getUrlsAttribute()
    {
        return json_decode($this->attributes['urls'], true);
    }

    public function mko(): HasOne
    {
        return $this->hasOne(GeneralCompany::class, 'id', 'mko_id');
    }
}
