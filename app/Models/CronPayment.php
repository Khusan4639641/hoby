<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CronPayment extends Model
{
    const PAYMENT_ACCOUNT = 2;
    const PAYMENT_CARD = 1;
    const PAYMENT_CARD_PINFL = 3;

    public function buyer(){
        return $this->hasone(Buyer::class,'id','user_id');
    }

}
