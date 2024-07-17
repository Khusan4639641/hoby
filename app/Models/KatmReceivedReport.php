<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KatmReceivedReport extends Model
{

    public const STATUS_AWAIT = 0;
    public const STATUS_COMPLETE = 1;
    public const STATUS_BROKEN = 2;

    public const TYPE_START = 'start';

    protected $fillable = [
        'report_type',
        'contract_id ',
        'status',
        'error_response',
        'received_date',
        'token',
        'file_id',
    ];

    protected $attributes = [
        'status' => self::STATUS_AWAIT,
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function scopeStatus(Builder $query, int $status): Builder
    {
        return $query->where('status', $status);
    }

}
