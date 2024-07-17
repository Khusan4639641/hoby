<?php

namespace App\Http\Controllers\V3;

use App\Http\Controllers\Controller;
use App\Models\V3\District;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DistrictController extends Controller
{
    public function all(Request $request): JsonResponse
    {
        $districts = District::query();

        if ($request->filled('region_id')) {
            $districts->whereRegionId($request->region_id);
        }
        return response()->json($districts->get());
    }
}
