<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'balance',
        'user_id',
        'account',
        'closed_at'
    ];

    public function getBalance() : float
    {
        return $this->balance;
    }

    public function setBalance(float $amount) : void
    {
        $this->balance = $amount;
        $this->save();
    }


}
