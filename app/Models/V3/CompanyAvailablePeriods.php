<?php

namespace App\Models\V3;

use App\Models\AvailablePeriod;
use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


/**
 * @mixin Builder
 */
class CompanyAvailablePeriods extends Model
{
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function available_periods(): BelongsTo
    {
        return $this->belongsTo(AvailablePeriod::class,'period_id','id');
    }

}
