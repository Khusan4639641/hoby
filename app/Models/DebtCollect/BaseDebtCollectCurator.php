<?php

namespace App\Models\DebtCollect;

use DDZobov\PivotSoftDeletes\Concerns\HasRelationships;
use Illuminate\Database\Eloquent\Builder;

use App\Models\User;

use App\Models\V3\District;
use App\Models\V3\Region;

use App\Scopes\BaseCollectCuratorScope;

class BaseDebtCollectCurator extends User
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
    protected $appends = ['full_name', 'regions'];

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
        return $this->belongsToMany(District::class, 'debt_collect_curator_district', 'curator_id')
            ->using(DebtCollectCuratorDistrict::class)
            ->withSoftDeletes()
            ->withTimestamps()
            ->withPivot([
                'deleted_at'
            ]);
    }

    public function getRegionsAttribute() {
        $groupedDistricts = $this->districts()->get(['districts.id', 'districts.name', 'districts.region_id'])->groupBy('region_id');
        $attachedRegions = Region::whereIn('id', $groupedDistricts->keys())->with(['districts' => function($query) {
            $query->select('id', 'region_id');
        }])->get(['id', 'name']);
        return $attachedRegions->map(function($region) use($groupedDistricts) {
            $regionDistricts = $groupedDistricts[$region->id];
            $missedDistricts = array_diff($region->districts->pluck('id')->all(), $regionDistricts->pluck('id')->all());

            return [
                "id" => $region->id,
                "name" => $region->name,
                "districts" => $regionDistricts->map(function($district) {
                    return collect($district->toArray())->only(['id', 'name'])->all();
                }),
                "all_districts_attached" => count($missedDistricts) === 0
            ];
        });
    }

    public function debtor_actions()
    {
        return $this->hasMany(DebtCollectDebtorAction::class, 'collect_agent_id');
    }

    protected static function booted()
    {
        static::addGlobalScope(new BaseCollectCuratorScope());
    }
}
