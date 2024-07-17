<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AccountBalanceHistory1C extends Model
{
    protected $table = 'account_balance_histories_1c';

    public function mfoAccount(): BelongsTo
    {
        return $this->belongsTo(MFOAccount::class, 'mfo_account_id', 'id');
    }

    public function latestBalance(): HasOne
    {
        return $this->hasOne(self::class, 'mfo_account_id', 'mfo_account_id')
            ->latest('operation_date');
    }
}
