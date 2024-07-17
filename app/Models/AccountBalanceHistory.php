<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountBalanceHistory extends Model
{
    protected $table = 'account_balance_histories';

    protected $fillable = ['account_id','operation_date','balance'];

    protected $dates = ['operation_date'];

    public function account() : BelongsTo
    {
        return $this->belongsTo(Account::class,'account_id');
    }
}
