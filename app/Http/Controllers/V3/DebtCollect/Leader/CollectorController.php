<?php

namespace App\Http\Controllers\V3\DebtCollect\Leader;

use App\Http\Controllers\Controller;
use App\Models\DebtCollect\DebtCollector;
use App\Models\V3\District;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CollectorController extends Controller
{
    public function all(Request $request)
    {
        return DebtCollector::search($request->search)->paginate(null, ['id', 'name', 'surname', 'patronymic', 'phone']);
    }

    public function getCollector(DebtCollector $debt_collector)
    {
        return $debt_collector->load('districts')->append('curators');
    }

    public function getRegions(Request $request, DebtCollector $collector)
    {
        return $collector->regions;
    }

    public function getActualDebtors(Request $request, DebtCollector $debt_collector)
    {
        return $debt_collector->debtors()
            ->withOverdueContracts(1)
            ->search($request->search)
            ->with(['region', 'district'])
            ->paginate();
    }

    public function getCollectorsDistricts(Request $request) {
        $except_collector_id = $request->get('except_collector_id') ?? [];

        return District::whereHas('debt_collectors', function($query) use($except_collector_id) {
            $query->whereNotIn('users.id', $except_collector_id);
        })->pluck('id');
    }

    public function syncDistricts(Request $request, DebtCollector $collector)
    {
        return $collector->districts()->sync($request->district_id);
    }

    public function analyticPayments(Request $request) {
        $collectors = DebtCollector::all();
        return [
            'total' => round($collectors->reduce(function($carry, $collector) {
                    return $carry + $collector->debt_collected_sum;
                }, 0), 2),
            'remunerations' => round($collectors->reduce(function($carry, $collector) {
                return $carry + $collector->remunerations;
            }, 0), 2),
        ];
    }

    public function analytic(Request $request)
    {
        $start_at = Carbon::now()->startOfMonth();
        $end_at = Carbon::now()->endOfMonth();
        if($request->has('month')) {
            $month = Carbon::parse($request->month);
            $start_at = $month->copy()->startOfMonth();
            $end_at = $month->copy()->endOfMonth();
        }
        return DebtCollector::search($request->search)->withCount([
            'debtor_actions as action_location_count' => function($query) use($start_at, $end_at) {
                $query->where('type', 'location')->where('created_at', '>=', $start_at);
            },
            'debtor_actions as action_photo_count' => function($query) use($start_at, $end_at) {
                $query->where('type', 'photo')->whereBetween('created_at', [$start_at, $end_at]);
            },
            'debtor_actions as action_date_count' => function($query) use($start_at, $end_at) {
                $query->where('type', 'date')->whereBetween('created_at', [$start_at, $end_at]);
            },
            'debtor_actions as action_text_count' => function($query) use($start_at, $end_at) {
                $query->where('type', 'text')->whereBetween('created_at', [$start_at, $end_at]);
            },
        ])->paginate();
    }
}
