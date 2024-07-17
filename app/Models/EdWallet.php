<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdWallet extends Model
{
    protected $table = 'ed_wallet';

    protected $fillable = [
        'doc_date',
        'account',
        'name',
        'doc_id',
        'doc_type',
        'filial',
        'turnover_debit',
        'turnover_credit',
        'purpose_of_payment',
        'cash_symbol',
        'inn'
    ];

}
