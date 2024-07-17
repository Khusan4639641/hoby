<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MFOAccount extends Model
{
    protected $table = 'mfo_accounts';

    public function accounts1C(): BelongsToMany
    {
        return $this->belongsToMany(Account1C::class, 'account_1c_mfo_account',
                                    'mfo_account_id', 'account_1c_id');
    }

    public function balanceHistories(): HasMany
    {
        return $this->hasMany(AccountBalanceHistory1C::class, 'mfo_account_id');
    }
}
