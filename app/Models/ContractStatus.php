<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ContractStatus extends Model
{
    protected $fillable = ['contract_id', 'status'];

    CONST CONTRACT_TYPE_MFO = 'mfo';
    CONST CONTRACT_TYPE_INSTALLMENT = 'installment';//рассрочка

    const STATUS_ORDER_CREATED_NOT_VERIFIED = 0;
    const STATUS_ORDER_CLIENT_SIGN = 20;
    const STATUS_ORDER_LIVELINESS_DETECTION_PENDING_APPLICATION = 33;
    const STATUS_ORDER_LIVELINESS_DETECTION_APPLICATION_REJECT = 34;
    const STATUS_ORDER_LIVELINESS_DETECTION_APPLICATION_APPROVE = 35;
    const STATUS_ORDER_VERIFIED = 50;

    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class);
    }
}
