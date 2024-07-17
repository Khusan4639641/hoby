<?php

namespace App\Services\API\V3\Partners;

use App\Models\Region;
use App\Services\API\V3\BaseService;
use Illuminate\Http\Request;

class RegionService extends BaseService
{
    public static function list( Request $request)
    {
        $result = Region::orderBy('regionid','DESC')->get();
        return self::handleResponse($result);
    }
}