<?php

namespace App\Http\Controllers\Admin;

use App\Aggregators\DebtCollectActionsAggregators\ActionsAggregator;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use Symfony\Component\HttpFoundation\Request;

class DeltaController extends Controller
{
    public function getActions(Request $request, Contract $contract)
    {
        return ActionsAggregator::getForDeltaByContract($contract->id);
    }
}