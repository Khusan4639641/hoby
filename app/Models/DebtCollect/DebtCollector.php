<?php

namespace App\Models\DebtCollect;

use App\Models\User;
use App\Models\V3\District;
use App\Models\V3\Region;
use App\Scopes\DebtCollectScope;
use Carbon\Carbon;
use DDZobov\PivotSoftDeletes\Concerns\HasRelationships;
use Illuminate\Database\Eloquent\Builder;

class DebtCollector extends User
{
    use HasRelationships;

    /**
     * The relations to append to the model's array form.
     *
     * @var array
     */
    protected  $with = [];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['full_name', 'regions', 'contracts_count', 'processed_contracts_count', 'debt_collected_sum', 'remunerations', 'processed_debtors_count', 'debtors_count'];

    /**
     * @var mixed
     */

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
            ->orWhereHas('districts', function($query) use($searchLike) {
                $query->where('districts.name', 'LIKE', $searchLike)
                    ->orWhereHas('region', function ($query) use($searchLike) {
                        $query->where('regions.name', 'LIKE', $searchLike);
                    });
            });
    }

    public function districts()
    {
        return $this->belongsToMany(District::class, 'debt_collector_district', 'collector_id')
            ->using(DebtCollectorDistrict::class)
            ->withSoftDeletes()
            ->withTimestamps()
            ->withPivot([
                'deleted_at'
            ]);
    }

    public function getCuratorsAttribute()
    {
        $collector_districts = $this->districts()->pluck('districts.id');
        return DebtCollectCurator::whereHas('districts', function($query) use($collector_districts) {
            $query->whereIn('districts.id', $collector_districts);
        })->get();
    }

    public function getRegionsAttribute() {
        $groupedDistricts = $this->districts()->get(['districts.id', 'districts.name', 'districts.region_id'])->groupBy('region_id');
        $attachedRegions = Region::whereIn('id', $groupedDistricts->keys())->with(['districts' => function($query) {
            $query->select('id', 'region_id');
        }])->get(['id', 'name']);
        return $attachedRegions->map(function($region) use($groupedDistricts) {
            $regionDistricts = $groupedDistricts[$region->id];

            return [
                "id" => $region->id,
                "name" => $region->name,
                "districts" => $regionDistricts->map(function($district) {
                    return collect($district->toArray())->only(['id', 'name'])->all();
                })
            ];
        });
    }

    public function debtorsWithTrashed()
    {
        return $this->belongsToMany(Debtor::class, 'debt_collector_debtor', 'collector_id', 'debtor_id')
            ->withTimestamps()
            ->withPivot([
                'deleted_at'
            ]);
    }

    public function debtors()
    {
        return $this->debtorsWithTrashed()->withSoftDeletes();
    }

    public function getDebtorsCountAttribute()
    {
        return $this->currentContracts()->distinct('user_id')->count();
    }

    public function contracts()
    {
        return $this->belongsToMany(DebtCollectContract::class, 'debt_collector_contract', 'collector_id', 'contract_id')
            ->withSoftDeletes()
            ->withTimestamps();
    }

    public function debtor_actions()
    {
        return $this->hasMany(DebtCollectDebtorAction::class, 'collect_agent_id');
    }

    public function contract_actions()
    {
        return $this->hasMany(DebtCollectContractAction::class, 'collect_agent_id');
    }

    public function actions_processed() {
        return $this->hasMany(DebtCollectContractProcessed::class, 'collector_id');
    }

    public function currentContracts() {
        return $this->contracts()
            ->withTrashedPivots()
            ->where(function($query) {
                $query->where('debt_collector_contract.deleted_at', null)->orWhere('debt_collector_contract.deleted_at', '>=', Carbon::now()->startOfMonth());
            })
            ->where(function ($query) {
                $query->whereIn('status', [1, 3, 4])
                    ->orWhere(function ($query) {
                        $query->where('status', 9)->where('closed_at', '>=', Carbon::now()->startOfMonth());
                    });
            });
    }

    public function getContractsCountAttribute() {
        return $this->currentContracts()->count();
    }

    public function processedContracts() {
        $collector_id = $this->id;
        return $this->currentContracts()
            ->whereHas('actions_processed', function($query) use($collector_id) {
                $query->where('collector_id', $collector_id)->where('period_end_at', '>=', Carbon::now()->startOfMonth());
            });
    }

    public function getProcessedContractsCountAttribute() {
        return $this->processedContracts()->count();
    }

    public function getProcessedDebtorsCountAttribute() {
        return $this->processedContracts()->distinct('user_id')->count();
    }

    public function getDebtCollectedSumAttribute() {
        $debt_collected_sum = 0;

        $contracts = $this->processedContracts()->get()->unique('id');
        foreach($contracts as $contract) {
            $debt_collected_sum += $contract->debt_collect_results()
                ->where('collector_id', $this->id)
                ->withTrashedPivots()
                ->where('period_start_at', '>=', Carbon::now()->startOfMonth())
                ->get()
                ->reduce(function($carry, $result) {
                    return $carry + $result->payments()->sum('amount');
                }, 0);
        }
        return round($debt_collected_sum, 2);
    }

    public function getRemunerationsAttribute() {
        $remuneration = 0;

        $contracts = $this->processedContracts()->get()->unique('id');
        foreach($contracts as $contract) {
            $remuneration += $contract->debt_collect_results()
                ->where('collector_id', $this->id)
                ->where('period_start_at', '>=', Carbon::now()->startOfMonth())
                ->withTrashedPivots()
                ->get()
                ->reduce(function($carry, $result) {
                return $carry + ($result->payments()->sum('amount') * ($result->rate / 100));
            }, 0);
        }
        return round($remuneration, 2);
    }

    public function contractProcessedAt($contract_id) {
        $action_processed = $this->actions_processed()
            ->where('contract_id', $contract_id)
            ->orderByDesc('period_start_at')
            ->first();

        return $action_processed->period_start_at ?? false;
    }

    public function debtorProcessedAt($debtor_id) {
        $debtor = $this->debtors()->where('users.id', $debtor_id)->with('contracts')->first();

        if(!$debtor) {
            return false;
        }

        $contract = $this->contracts()->whereIn('contracts.id', $debtor->contracts->pluck('id'))->first();

        if(!$contract) {
            return false;
        }

        return $this->contractProcessedAt($contract->id);
    }

    public function debtorLastProcessedAt($debtor_id) {
        return $this->debtorProcessedAt($debtor_id);
    }

    protected static function booted()
    {
        static::addGlobalScope(new DebtCollectScope());
    }
}
