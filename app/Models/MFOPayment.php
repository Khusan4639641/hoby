<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MFOPayment extends Model
{
    protected $table = 'mfo_payments';

    const TYPE_LOAN = 'loan';
    const TYPE_PAYMENT = 'payment';
    const TYPE_SERVICE = 'service';
    const TYPE_CANCEL_LOAN = 'cancel_loan';
    const TYPE_CANCEL_PAYMENT = 'cancel_payment';
    const TYPE_CANCEL_SERVICE = 'cancel_service';

    const STATUS_ACTIVE = 1;
    const STATUS_CANCEL = 2;

    protected $fillable = [
        'user_id',
        'type',
        'status',
        'contract_id',
        'amount'
    ];

    public function wallets()
    {
        return $this->hasOne(Wallet::class,'user_id','user_id');
    }
}
