<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingEntry extends Model
{
    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    public const CODE_0000 = '0000';
    public const CODE_1007 = '1007';
    public const CODE_1008 = '1008';
    public const CODE_1009 = '1009';

    protected $table = 'accounting_entries';

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
        self::created(function ($model) {
            $model->payment_id = str_pad($model->id, 20, '0', STR_PAD_LEFT);
            $model->save();
        });
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'debit_account', 'number');
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'credit_account', 'number');
    }

}
