<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MFOEventPayment extends Model
{
    protected $table = 'MFO_events_payments';

    const STATUS_PROCESSED = 1;
    const STATUS_NOT_PROCESSED = 0;

    protected $fillable = [
        'contract_id',
        'user_id',
        'schedule_id',
        'amount',
        'type',
        'payment_system',
        'status',
        'record_created_at',
        'record_updated_at',
    ];

    public $timestamps = false;
}
