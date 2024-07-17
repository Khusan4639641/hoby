<?php

namespace App\Http\Controllers\V3\DebtCollect\Collector;

use App\Http\Controllers\Controller;
use App\Models\DebtCollect\DebtCollector;
use Illuminate\Support\Facades\Auth;

class CollectorController extends Controller
{
    private function collector() {
        return DebtCollector::findOrFail(Auth::user()->id);
    }
    public function profile()
    {
        $collector = $this->collector();
        return [
            'id' => $collector->id,
            'full_name' => $collector->full_name,
            'phone' => $collector->phone,
            'remunerations' => $collector->remunerations
        ];
    }
    public function getDistricts()
    {
        $debtors_id = $this->collector()->debtors()->withOverdueContracts(1)->pluck('users.id');
        return $this->collector()->districts()->with(['region', 'debtors' => function($query) use($debtors_id) {
            $query->whereIn('users.id', $debtors_id);
        }])->orderBy('region_id', 'desc')->get();
    }
}
