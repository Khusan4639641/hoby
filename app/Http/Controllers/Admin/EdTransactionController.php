<?php

namespace App\Http\Controllers\Admin;

use App\Exports\EdTransactionExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\EdTransactionResource;
use App\Models\EdTransaction;
use App\Services\API\V3\BaseService;
use App\Services\resusBank\AccountBalanceService;
use App\Services\EdTransactionService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel;
use Validator;

class EdTransactionController extends Controller
{

    private Excel $excel;

    public function __construct(Excel $excel)
    {
        $this->excel = $excel;
    }

    public function filter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date_format:d.m.Y',
            'date_to' => 'nullable|date:d.m.Y|after_or_equal:date_from',
            'type' => 'nullable|string|in:credit,debit'
        ]);

        if ($validator->fails()) {
            BaseService::handleError($validator->errors()->getMessages());
        }

        $validated = $validator->validate();

        $query = EdTransaction::query()
            ->when($request->has('date_from'), function (Builder $builder) use ($validated) {
                $builder->where('doc_time', '>=', Carbon::createFromFormat('d.m.Y', $validated['date_from'])->startOfDay()->getTimestampMs());
            })
            ->when($request->has('date_to'), function (Builder $builder) use ($validated) {
                $builder->where('doc_time', '<=', Carbon::createFromFormat('d.m.Y', $validated['date_to'])->endOfDay()->getTimestampMs());
            })->orderByDesc('doc_time');

        $statistics_query = clone $query;

        $data = $statistics_query->select(\DB::raw('SUM(amount) as total, type'))->groupBy('type')->get();

        $statistics = [
            'debit' => 0,
            'credit' => 0,
            'close' => 0,
            'close_bank' => AccountBalanceService::getBalanceDataFromCacheOrService()['balance'],

        ];
        foreach ($data as $d) {
            $statistics[strtolower($d['type'])] = intval($d['total']);
        }
        $statistics['close'] = $statistics['credit'] - $statistics['debit'];

        $items = $query->when($request->has('type'), function (Builder $builder) use ($validated) {
            $builder->where('type', strtoupper($validated['type']));
        })->paginate(15);

        return BaseService::handleResponse([
            'statistics' => $statistics,
            'data' => EdTransactionResource::collection($items),
            'links' => [
                'self_page_url' => $items->url($items->currentPage()),
                'next_page_url' => $items->nextPageUrl(),
                'prev_page_url' => $items->previousPageUrl(),
                'first_page_url' => $items->url(1),
                'last_page_url' => $items->url($items->lastPage()),
                "current_page" => $items->currentPage(),
                "last_page" => $items->lastPage(),
                "per_page" => $items->perPage(),
                "total" => $items->total()
            ],
        ]);
    }

    public function downloadReport(Request $request)
    {
        EdTransactionService::balanceBetweenPeriod(
            Carbon::now(),
            Carbon::now()
        );
        $validator = Validator::make($request->all(), [
            'date_from' => 'required|date_format:d.m.Y',
            'date_to' => 'required|date:d.m.Y|after_or_equal:date_from',
            'type' => 'nullable|string|in:credit,debit'
        ]);

        if ($validator->fails()) {
            BaseService::handleError($validator->errors()->getMessages());
        }

        $validated = $validator->validate();

        return $this->excel->download(new EdTransactionExport(
            Carbon::createFromFormat('d.m.Y', $validated['date_from']),
            Carbon::createFromFormat('d.m.Y', $validated['date_to']),
            $validated['type'] ?? ''
        ), 'TransactionExport.xlsx');
    }

}
