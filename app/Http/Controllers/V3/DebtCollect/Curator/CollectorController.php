<?php

namespace App\Http\Controllers\V3\DebtCollect\Curator;

use App\Http\Controllers\Controller;

use App\Http\Requests\DebtCollect\AttachDebtorRequest;
use App\Models\Contract;
use App\Models\DebtCollect\DebtCollectContract;
use App\Models\DebtCollect\DebtCollectContractResult;
use App\Models\DebtCollect\DebtCollectCurator;
use App\Models\DebtCollect\DebtCollector;
use App\Models\DebtCollect\Debtor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CollectorController extends Controller
{
    public function getCollectors(Request $request) {
        // 27.04.2023 Nurlan обнаружил что этот код не используется
//        $start_at = Carbon::now()->startOfMonth();
//        $end_at = Carbon::now()->endOfMonth();
//        if($request->has('month')) {
//            $month = Carbon::parse($request->month);
//            $start_at = $month->startOfMonth();
//            $end_at = $month->endOfMonth();
//        }

        $curator = DebtCollectCurator::findOrFail(Auth::id());
        $districts = $curator->districts()->pluck('districts.id');
        return DebtCollector::search($request->search)->withCount([
            'debtor_actions as action_location_count' => function($query) {
                $query->where('type', 'location');
            },
            'debtor_actions as action_photo_count' => function($query) {
                $query->where('type', 'photo');
            },
            'debtor_actions as action_date_count' => function($query) {
                $query->where('type', 'date');
            },
            'debtor_actions as action_text_count' => function($query) {
                $query->where('type', 'text');
            },
        ])
            ->whereHas('districts', function($query) use($districts) {
                $query->whereIn('districts.id', $districts);
            })
            ->paginate(null, ['users.id', 'name', 'surname', 'patronymic', 'phone'])
        ;
    }

    public function getCollector(DebtCollector $debt_collector)
    {
        return response()->json($debt_collector->load('districts'));
    }

    public function getActualDebtors(Request $request, DebtCollector $debt_collector)
    {
        return $debt_collector->debtors()
            ->withOverdueContracts(1)
            ->search($request->search)
            ->with(['region', 'district', 'addressRegistration'])
            ->leftJoin('buyer_addresses', 'users.id', '=', 'buyer_addresses.user_id')
            ->where('buyer_addresses.type', '=', 'registration')
            ->orderBy('buyer_addresses.address', 'asc')
            ->paginate(null, [
                'users.id', 'users.name', 'users.surname', 'users.patronymic',
                'users.region', 'users.local_region',
            ]);
    }

    public function getPotentialDebtors(Request $request, DebtCollector $debt_collector)
    {
        $curator_districts_id = DebtCollectCurator::findOrFail(Auth::user()->id)->districts()->pluck('districts.id')->all();
        $collector_districts_id = $debt_collector->districts()->pluck('districts.id')->all();
        $potential_districts_id = array_intersect($curator_districts_id, $collector_districts_id);
        return Debtor::search($request->search)->with(['region', 'district', 'addressRegistration'])
            ->leftJoin('buyer_addresses', 'users.id', '=', 'buyer_addresses.user_id')
            ->where('buyer_addresses.type', '=', 'registration')
            ->orderBy('buyer_addresses.address', 'asc')
            ->whereHas('district', function($query) use($potential_districts_id) {
                $query->whereIn('districts.id', $potential_districts_id);
            })->whereRaw('users.id NOT IN (SELECT debtor_id FROM debt_collector_debtor WHERE deleted_at IS NULL)')
            ->withOverdueContracts($request->delays ?? 90)
            ->paginate(null, [
                'users.id', 'users.name', 'users.surname', 'users.patronymic',
                'users.region', 'users.local_region',
            ]);
    }

    public function attachDebtors(AttachDebtorRequest $request, DebtCollector $debt_collector)
    {
        $debt_collector->debtors()->attach($request->debtor_id);
        $contracts_id = DebtCollectContract::whereIn('user_id', $request->debtor_id)->pluck('id');
        $debt_collector->contracts()->attach($contracts_id);
        $contracts = $debt_collector->contracts()->whereIn('contracts.id', $contracts_id)->get();

        foreach ($contracts as $contract) {
            $rate = 0;
            if($contract->expired_days >= 300) {
                $rate = 7;
            } else if($contract->expired_days >= 201) {
                $rate = 5;
            } else if($contract->expired_days >= 140) {
                $rate = 4;
            }  else {
                $rate = 3;
            }

            $contract->debt_collect_results()->create([
                'collector_id' => $debt_collector->id,
                'period_start_at' => Carbon::now(),
                'rate' => $rate
            ]);
        }

        return response()->json();
    }

    public function detachDebtors(Request $request, DebtCollector $debt_collector)
    {
        $contracts_id = Contract::whereIn('user_id', $request->debtor_id)->pluck('id');

        DebtCollectContractResult::whereIn('contract_id', $contracts_id)
            ->whereNull('period_end_at')
            ->where('collector_id', $debt_collector->id)->update([
            'period_end_at' => Carbon::now()
        ]);

        $debt_collector->contracts()->detach($contracts_id);
        $debt_collector->debtors()->detach($request->debtor_id);

        return response()->json();
    }
}
