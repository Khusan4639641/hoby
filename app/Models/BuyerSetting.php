<?php

namespace App\Models;

use App\Helpers\PaymentHelper;
use Illuminate\Database\Eloquent\Model;

class BuyerSetting extends Model
{
    protected $fillable = [
        'user_id',
        'period',
        'limit',
        'balance',
        'rating',
        'zcoin',
        'paycoin',
        'paycoin_month',
        'paycoin_sale',
        'paycoin_limit',
        'personal_account',
        'katm_region_id',
        'katm_local_region_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function buyer()
    {
        return $this->belongsTo(Buyer::class, 'user_id', 'id');
    }

}
