<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerBonus extends Model
{

    const BONUS_STATUS_NOT_ACTIVE = 0;
    const BONUS_STATUS_ACTIVE = 1;

    const BONUS_TYPE_FILL = 'fill';
    const BONUS_TYPE_REFUND = 'refund';

    public function contract()
    {
        return $this->hasOne(Contract::class, 'id', 'contract_id');
    }

    public function seller()
    {
        return $this->hasOne(Saller::class, 'id', 'seller_id');
    }

    public function buyer()
    {
        return $this->hasOne(Buyer::class, 'id', 'seller_id');
    }

    public function sett()
    {
        return $this->hasOne(BuyerSetting::class, 'user_id', 'seller_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function activeFillPayment()
    {
        return $this->payment()->where([
            ['status', Payment::PAYMENT_STATUS_ACTIVE], // 1
            ['type', Payment::PAYMENT_TYPE_FILL_ACCOUNT], // fill
            ['payment_system', Payment::PAYMENT_SYSTEM_PAYCOIN] // Paycoin
        ]);
    }
}
