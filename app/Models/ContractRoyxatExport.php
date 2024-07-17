<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractRoyxatExport extends Model
{
    protected $fillable = [
        'credit_id',
        'credit_status',
        'overdue_days'
    ];
}
