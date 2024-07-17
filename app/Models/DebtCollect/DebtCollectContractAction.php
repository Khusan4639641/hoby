<?php

namespace App\Models\DebtCollect;

use App\Models\Buyer;
use App\Models\V3\District;
use App\Scopes\DebtCollectorScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebtCollectContractAction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'type',
        'content',
        'contract_id'
    ];
}
