<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Account1C extends Model
{
    protected $table = 'accounts_1c';

    public function mfoAccounts(): BelongsToMany
    {
        return $this->belongsToMany(MFOAccount::class, 'account_1c_mfo_account',
                                    'account_1c_id', 'mfo_account_id');
    }
}
