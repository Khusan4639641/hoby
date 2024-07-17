<?php

namespace App\Http\Controllers\V3;

use App\Http\Controllers\Controller;
use App\Models\V3\Region;

class RegionController extends Controller
{
    public function all()
    {
        return response()->json(Region::all());
    }
}
