<?php

namespace App\Models\DebtCollect;

use Illuminate\Database\Eloquent\Model;

class DebtCollectContractProcessed extends Model
{
    protected $table = 'debt_collect_contracts_processed';

    protected $fillable = ['collector_id', 'contract_id', 'period_start_at', 'period_end_at'];
}
