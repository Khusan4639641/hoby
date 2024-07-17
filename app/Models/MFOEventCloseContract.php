<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MFOEventCloseContract extends Model
{
    protected $table = 'MFO_events_close_contracts';

    const STATUS_PROCESSED = 1;
    const STATUS_NOT_PROCESSED = 0;

    protected $fillable = [
        'contract_id',
        'user_id',
        'contracts_close_date',
        'status',
        'record_created_at',
        'record_updated_at',
    ];

    public $timestamps = false;
}
