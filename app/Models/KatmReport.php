<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KatmReport extends Model
{

    public const STATUS_AWAIT = 0;
    public const STATUS_COMPLETE = 1;
    public const STATUS_BROKEN = 2;
    public const STATUS_REPEAT = 3;

    public const TYPE_PRE_REGISTRATION = 'pre_registration';
    public const TYPE_REGISTRATION = 'registration';
    public const TYPE_CANCEL = 'cancel';
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_COMPLETE = 'complete';

    public const NUMBER_LOAN_REG = '001';
    public const NUMBER_START = 'start';
    public const NUMBER_START_STATUS = 'start_status';
    public const NUMBER_LOAN_AGREEMENT = '004';
    public const NUMBER_SCHEDULES = '005';
    public const NUMBER_BALANCES = '015';
    public const NUMBER_PAYMENTS = '016';
    public const NUMBER_ACCOUNTS_STATUSES = '018';
    public const NUMBER_REFUSE = '003';

    protected $fillable = [
        'report_number',
        'report_type',
        'contract_id',
        'status',
        'order',
        'body',
        'error_response',
        'sent_date',
        'hash',
    ];

    protected $attributes = [
        'status' => self::STATUS_AWAIT,
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function scopeStatus(Builder $query, int $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeStatusNot(Builder $query, int $status): Builder
    {
        return $query->where('status', '!=', $status);
    }

    public function scopeSorted(Builder $query): Builder
    {
        return $query->orderBy('order');
    }

}
