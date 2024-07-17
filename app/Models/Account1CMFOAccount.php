<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Account1CMFOAccount extends Model
{
    protected $table = 'account_1c_mfo_account';

    public $timestamps = false;

    public function accounts1c()
    {
        return $this->hasMany(Account1C::class, 'id', 'account_1c_id');
    }

    public function mfoAccounts()
    {
        return $this->hasMany(MFOAccount::class, 'id', 'mfo_account_id');
    }
}
