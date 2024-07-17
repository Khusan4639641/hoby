<?php

namespace App\Http\Controllers\V3;

use App\Services\API\V3\UnitService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UnitController extends CoreController
{
    public function list(Request $request, UnitService $service)
    {
        return $service->list($request);
    }
}
