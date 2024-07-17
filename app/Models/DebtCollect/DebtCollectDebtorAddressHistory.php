<?php

namespace App\Models\DebtCollect;

use Illuminate\Database\Eloquent\Model;

class DebtCollectDebtorAddressHistory extends Model
{
    protected $table = 'debt_collect_debtor_address_history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'debtor_id',
        'past_address',
        'new_address',
        'changer_id',
        'comment',
        'file_path',
    ];

}
