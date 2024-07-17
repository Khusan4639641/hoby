<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountBalanceHistoryCBU extends Model
{
    protected $table = 'account_balance_histories_cbu';

    protected $fillable = ['account_id','operation_date','balance'];

    protected $dates = ['operation_date'];
}
