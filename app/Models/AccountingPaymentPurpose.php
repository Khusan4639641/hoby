<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingPaymentPurpose extends Model
{

    public const TYPE_SENDER = 1;
    public const TYPE_RECEIVER = 2;

    protected $fillable = [
        'code',
        'title',
        'company_type',
    ];

}
