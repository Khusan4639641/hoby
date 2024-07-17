<?php

namespace App\Http\Controllers\Web\Panel;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\Controller;

use \App\Models\Contract;
use \App\Models\Collector;
use \App\Models\CollectorTransaction;

class CollectorTransactionController extends Controller
{
    function get(Request $request, Contract $contract) {
        $collector = Collector::whereHas('contracts', function(Builder $query) use($contract) {
            $query->where('contracts.id', $contract->id);
        })->first();

        $contractPivot = $collector->contracts()->find($contract->id)->pivot->id;


        $collectorTransactions = CollectorTransaction::where('collector_contract_id', $contractPivot)->get();

        return view('panel.recovery.collectors.transactions', [
            'collector' => $collector,
            'contract' => $contract
        ]);
    }

}
