<?php

namespace App\Models\V3;

use App\Models\DebtCollect\BaseDebtCollectCurator;
use App\Models\DebtCollect\DebtCollectCuratorDistrict;
use App\Models\DebtCollect\DebtCollector;
use App\Models\DebtCollect\DebtCollectorDistrict;
use App\Models\DebtCollect\Debtor;
use DDZobov\PivotSoftDeletes\Concerns\HasRelationships;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class District extends Model
{
    use SoftDeletes;
    use HasRelationships;

    protected $fillable = [
        'name',
        'cbu_id',
        'iiv_id',
        'postal_id'
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function debt_collect_curators() {
        return $this->belongsToMany(BaseDebtCollectCurator::class, 'debt_collect_curator_district', 'district_id', 'curator_id')
            ->using(DebtCollectCuratorDistrict::class)
            ->withSoftDeletes()
            ->withTimestamps()
            ->withPivot([
                'deleted_at'
            ]);
    }

    public function debt_collectors() {
        return $this->belongsToMany(DebtCollector::class, 'debt_collector_district', 'district_id', 'collector_id')
            ->using(DebtCollectorDistrict::class)
            ->withSoftDeletes()
            ->withTimestamps()
            ->withPivot([
                'deleted_at'
            ]);
    }

    public function debtors() {
        return $this->hasMany(Debtor::class, 'local_region', 'cbu_id');
    }
}
