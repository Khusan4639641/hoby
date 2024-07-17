<?php

namespace App\Services\API\V3;

use App\Models\CatalogCategory;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UnitService extends BaseService
{
    public static function list(Request $request)
    {
        $lang = app()->getLocale();

        $data = Unit::select('units.id', 'unit_languages.title')
            ->leftJoin('unit_languages', 'unit_languages.unit_id', '=', 'units.id')
            ->where('unit_languages.language_code', $lang)
            ->get();

        return self::handleResponse($data);
    }
}
