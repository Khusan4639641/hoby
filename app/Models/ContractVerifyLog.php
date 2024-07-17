<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class ContractVerifyLog extends Model
{
    protected $table = 'contract_verify_logs';

    protected $fillable = [
        'contract_id',
        'order_product_id',
        'user_id',
        'old_name',
        'new_name',
        'old_category_id',
        'new_category_id',
        'old_unit_id',
        'new_unit_id',
    ];
}
