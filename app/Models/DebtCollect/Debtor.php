<?php

namespace App\Models\DebtCollect;

use App\Models\Buyer;
use App\Models\Contract;
use App\Models\ContractPaymentsSchedule;
use App\Models\Payment;
use App\Models\V3\District;
use App\Models\V3\Region;
use Carbon\Carbon;
use DDZobov\PivotSoftDeletes\Concerns\HasRelationships;
use Illuminate\Database\Eloquent\Builder;

class Debtor extends Buyer
{
    use HasRelationships;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['full_name', 'contracts_count', 'expired_contracts_count', 'debt_collect_sum', 'debt_collected_sum', 'expired_days', 'processed_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'local_region',
        'region',
    ];

    public function district()
    {
        return $this->belongsTo(District::class, 'local_region', 'cbu_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'region', 'cbu_id');
    }

    public function contracts()
    {
        return $this->hasMany(DebtCollectContract::class, 'user_id', 'id');
    }

    public function debts()
    {
        return $this->hasMany(ContractPaymentsSchedule::class, 'user_id');
    }

    public function debt_collect_payments() {
        return $this->hasMany(Payment::class, 'user_id');
    }

    public function collectorsWithTrashed() {
        return $this->belongsToMany(
            DebtCollector::class,
            'debt_collector_debtor',
            'debtor_id',
            'collector_id')
            ->withTimestamps()
            ->withPivot([
                'deleted_at'
            ]);
    }

    public function collectors() {
        return $this->collectorsWithTrashed()->withSoftDeletes();
    }

    public function debt_collect_actions()
    {
        return $this->hasMany(DebtCollectDebtorAction::class, 'debtor_id');
    }

    public function promised_payments()
    {
        return $this->hasMany(AddedActionHistory::class, 'client');
    }

    public function getContractsCountAttribute() {
        return $this->contracts()->whereIn('status', [1,3,4,9])->count();
    }

    public function getExpiredContractsCountAttribute() {
        return $this->contracts()
            ->whereIn('status', [3, 4])
            ->where('expired_days', '>=', 90)
            ->count();
    }

    public function getExpiredDaysAttribute() {
        $contract = $this->contracts()
            ->whereIn('status', [3, 4])
            ->orderBy('expired_days', 'desc')
            ->first();

        return $contract ? $contract->expired_days : 0;
    }

    public function getDebtCollectSumAttribute() {
        return round($this->contracts()->whereIn('status', [1,3,4,9])
            ->get()
            ->reduce(function($carry, $contract) {
            return $carry + $contract->debt_sum;
        }), 2);
    }

    public function getDebtCollectedSumAttribute() {
        return round($this->debt_collect_payments()
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->where('status', [1, -1])
            ->sum('amount')
        , 2);
    }

    public function getProcessedAtAttribute() {
        $collector = $this->collectors()->orderByDesc('created_at')->first();
        if(!$collector) {
            return null;
        }
        return $collector->debtorLastProcessedAt($this->id);
    }

    /**
     * Scope a query where are the contract parameters
     *
     * @param Builder $query
     * @param  int  $overdueDays
     * @return Builder
     */
    public function scopeWithOverdueContracts(Builder $query, int $overdueDays)
    {
        return $query->whereHas('contracts', function ($query) use ($overdueDays) {
            $query->whereIn('status', [
                Contract::STATUS_ACTIVE,
                Contract::STATUS_OVERDUE_60_DAYS,
                Contract::STATUS_OVERDUE_30_DAYS
            ]);
            if ($overdueDays > 0) {
                $query->where('expired_days', '>=', $overdueDays);
            }
        });
    }

    /**
     * Scope a query where id, name, surname, patronymic or phone LIKE search param.
     *
     * @param Builder $query
     * @param  mixed  $search
     * @return Builder
     */
    public function scopeSearch(Builder $query, $search)
    {
        $searchLike = "%{$search}%";
        return $query->where('users.id', 'LIKE', $searchLike)
            ->orWhere('name', 'LIKE', $searchLike)
            ->orWhere('surname', 'LIKE', $searchLike)
            ->orWhere('patronymic', 'LIKE', $searchLike)
            ->orWhere('phone', 'LIKE', $searchLike)
            ->orWhereHas('region', function($query) use($searchLike) {
                $query->where('name', 'LIKE', $searchLike);
            })
            ->orWhereHas('district', function($query) use($searchLike) {
                $query->where('name', 'LIKE', $searchLike);
            });
    }

//    protected static function booted()
//    {
//        static::addGlobalScope(new DebtorScope());
//    }
}
