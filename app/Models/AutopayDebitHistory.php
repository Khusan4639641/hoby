<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AutopayDebitHistory extends Model {

    public const STATUS_PAID = 1;
    public const STATUS_NOT_PAID = 0;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'autopay_debit_history';

    public function buyer(){
        return $this->belongsTo(Buyer::class, 'user_id', 'id');
    }

}
