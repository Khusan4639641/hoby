<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UzTax extends Model
{
    protected $table = 'uz_taxes';

    const IS_REFUND_SELL_PRODUCT = 0;
    const IS_REFUND_RETURN_PRODUCT = 1;

    const RECEIPT_TYPE_SELL = 0;
    const RECEIPT_TYPE_PREPAID = 1;
    const RECEIPT_TYPE_CREDIT = 2;

    const WAIT = 2;
    const ACCEPT = 1;
    const CANCEL = 0;

    const OFD_SERVER_ERROR = 500;
    const OFD_NOT_MATCH_TIN_ERROR = 600;
    const OFD_INCORRECT_PSIC_CODE = 700;

    public function payments()
    {
        return $this->hasMany(UzTax::class, 'contract_id','contract_id')->where([
            ['type', self::RECEIPT_TYPE_CREDIT],
            ['status', self::ACCEPT]
        ]);
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
