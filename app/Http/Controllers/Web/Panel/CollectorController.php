<?php

namespace App\Http\Controllers\Web\Panel;

use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\Controller;

use App\Models\Collector;
use App\Models\KatmRegion;
use App\Models\Buyer;

class CollectorController extends Controller
{
    function collectors(Request $request) {
        $katm_regions = KatmRegion::all();
        $regions = $katm_regions->unique('region')->values();

        return view('panel.recovery.collectors.index', [
            'regions' => $regions,
            'local_regions' => $katm_regions
        ]);
    }

    function contracts(Request $request) {
        $katm_regions = KatmRegion::all();
        $regions = $katm_regions->unique('region')->values();

        return view('panel.recovery.collectors.contracts', [
            'regions' => $regions,
            'local_regions' => $katm_regions
        ]);
    }
}
