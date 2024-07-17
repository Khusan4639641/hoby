<?php

namespace App\Services\Web\Panel;

use App\Http\Requests\{FromMkoReportListRequest};
use App\Models\MkoReport;

final class MkoToBankReportsService
{
    public static function getMkoReportsList(FromMkoReportListRequest $request)
    {
        return MkoReport::when($request->get('from'), function ($query) use ($request) {
            $query->whereDate('to', $request->get('to'))
                ->whereDate('from', $request->get('from'));
        })->when($request->get('offset'), function ($query) use ($request) {
            $query->where('id', '>=', $request->get('offset'));
        })->when($request->get('count'), function ($query) use ($request) {
            $query->take($request->get('count'));
        })->orderBy('id', 'desc')
            ->get();
    }
}
