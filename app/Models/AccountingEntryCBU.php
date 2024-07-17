<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingEntryCBU extends Model
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    protected $table = 'accounting_entries_cbu';

    protected $fillable = [
        'status',
        'operation_date',
        'debit_account',
        'credit_account',
        'amount',
        'description',
        'contract_id',
        'destination_code',
        'payment_id',
        'event_id',
    ];

    public static function boot()
    {
        parent::boot();
        self::created(function ($model){
            $model->payment_id = str_pad($model->id,20,'0',STR_PAD_LEFT);
            $model->save();
        });
    }

    public function contract() : BelongsTo
    {
        return $this->belongsTo(Contract::class,'contract_id');
    }
}
