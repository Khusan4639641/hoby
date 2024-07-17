<?php

namespace App\Http\Controllers\V3\DebtCollect\Leader;

use App\Http\Controllers\Controller;

use App\Models\DebtCollect\BaseDebtCollectCurator;
use Illuminate\Http\Request;

use App\Models\DebtCollect\DebtCollectCurator;
use App\Models\V3\District;

class CuratorController extends Controller
{
    public function all(Request $request)
    {
        return BaseDebtCollectCurator::search($request->search)->paginate(null, ['id', 'name', 'surname', 'patronymic', 'phone']);
    }

    public function getRegions(Request $request, BaseDebtCollectCurator $curator)
    {
        return $curator->regions;
    }

    public function getCuratorsDistricts(Request $request) {
        $except_curator_id = $request->get('except_curator_id') ?? [];

        return District::whereHas('debt_collect_curators', function($query) use($except_curator_id) {
            $query->whereNotIn('users.id', $except_curator_id);
        })->pluck('id');
    }

    public function syncDistricts(Request $request, BaseDebtCollectCurator $curator)
    {
        return $curator->districts()->sync($request->district_id);
    }
}
