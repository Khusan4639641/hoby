<?php

namespace App\Http\Controllers\Web\Panel;

use App\Helpers\NdsStopgagHelper;
use App\Http\Controllers\Core\NewsController as Controller;
use App\Models\Company;
use App\Models\Partner;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

use App\Models\News;
use Illuminate\Support\Facades\DB;


class GraphController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param News $news
     * @return RedirectResponse
     */
    public function index(Request $request)
    {
        $companies = Company::where('status', 1)->whereRaw('parent_id IS NULL')->orderBy('name')->get();
        $access = 'admin';
        return view('panel.graph.index', compact('access', 'companies'));
    }

    static private function getDivider() {
        return NdsStopgagHelper::getActualNdsPlusOne();
    }

    public function contracts(Request $request)
    {
        $pikachu = '';
        $date = report_filter($pikachu, 'date');

        $endDate = new Carbon($date['date_to']);
        $endDate = $endDate->format('Y-m-d');

        $dateText = $request->interval == 'week' ? '53 - WEEK(date, 1) AS group_date' : "FLOOR((UNIX_TIMESTAMP('" . $endDate . "') - UNIX_TIMESTAMP(t.date)) / (60 * 60 * 24 * " . $request->interval . ")) AS group_date";

        $created = new Carbon($date['date_from']);
        $interval = $created->subDay()->diff($date['date_to'])->days;

        $query = DB::query()->fromSub(function ($query) use ($interval) {
            $query->fromSub(function ($query) use ($interval) {
                $union = DB::query()->from('payments')
                    ->whereExists(function ($query) {
                        $query->from('contracts')
                            ->selectRaw("*")
                            ->whereRaw('payments.contract_id = contracts.id')
                            ->whereNotIn('status', [0, 2, 5]);
                    })
                    ->selectRaw('DATE_FORMAT(created_at, \'%Y-%m-%d\') AS date')
                    ->selectRaw("SUM(amount) as total")
                    ->groupBy('date')
                    ->orderBy('date');
                $query->fromSub(function ($query) use ($interval) {
                    $query->selectRaw('DATE_FORMAT(DATE_ADD(NOW(), INTERVAL - (@d:= 0) DAY), \'%Y-%m-%d\') AS date, 0 AS total')
                        ->union(DB::query()->from('payments')
                            ->selectRaw('DATE_FORMAT(DATE_ADD(NOW(), INTERVAL - (@d:= @d + 1) DAY), \'%Y-%m-%d\') AS date, 0 AS total')
                            ->limit($interval));
                }, 'tt')
                    ->union($union);
            }, 'ttt')
                ->selectRaw('ttt.date')
                ->selectRaw('SUM(ttt.total) as total')
                ->groupByRaw('date');
        }, 't')
            ->groupByRaw('group_date')
            ->orderByRaw('group_date DESC')
            ->selectRaw('DATE_FORMAT(MAX(date), \'%d.%m.%Y\') AS date')
            ->selectRaw("SUM(t.total) AS total")
            ->selectRaw($dateText);

        report_filter($query, 'date');

        $payments = $query->get();
        $this->result['status'] = 'success';
        $this->result['data'] = $payments;
        return $this->result();
    }

    public function clients(Request $request)
    {
        $pikachu = '';
        $date = report_filter($pikachu, 'date');

        $endDate = new Carbon($date['date_to']);
        $endDate = $endDate->format('Y-m-d');

        $dateText = $request->interval == 'week' ? '53 - WEEK(date, 1) AS group_date' : "FLOOR((UNIX_TIMESTAMP('" . $endDate . "') - UNIX_TIMESTAMP(t.date)) / (60 * 60 * 24 * " . $request->interval . ")) AS group_date";

        $created = new Carbon($date['date_from']);
        $interval = $created->subDay()->diff($date['date_to'])->days;

        $query = DB::query()->fromSub(function ($query) use ($interval) {
            $query->fromSub(function ($query) use ($interval) {
                $union = DB::query()->from('contracts')
                    ->selectRaw('DATE_FORMAT(contracts.created_at, \'%Y-%m-%d\') AS date')
                    ->selectRaw('SUM(contracts.total) as total')
                    ->selectRaw('SUM(contracts.total - orders.partner_total) as profit')
                    ->leftJoin('orders', 'orders.id', '=', 'contracts.order_id')
                    ->groupBy('date')
                    ->orderBy('date');
                $query->fromSub(function ($query) use ($interval) {
                    $query->selectRaw('DATE_FORMAT(DATE_ADD(NOW(), INTERVAL - (@d:= 0) DAY), \'%Y-%m-%d\') AS date, 0 AS total, 0 AS profit')
                        ->union(DB::query()->from('contracts')
                            ->selectRaw('DATE_FORMAT(DATE_ADD(NOW(), INTERVAL - (@d:= @d + 1) DAY), \'%Y-%m-%d\') AS date, 0 AS total, 0 AS profit')
                            ->limit($interval));
                }, 'tt')
                    ->union($union);
            }, 'ttt')
                ->selectRaw('ttt.date')
                ->selectRaw('SUM(ttt.total) as total')
                ->selectRaw('SUM(ttt.profit) as profit')
                ->groupByRaw('date');
        }, 't')
            ->groupByRaw('group_date')
            ->orderByRaw('group_date DESC')
            ->selectRaw('DATE_FORMAT(MAX(date), \'%d.%m.%Y\') AS date')
            ->selectRaw("SUM(t.total) AS total")
            ->selectRaw("SUM(t.total) / " . self::getDivider() . " AS total_without_nds")
            ->selectRaw("SUM(t.profit) AS profit")
            ->selectRaw($dateText);

        report_filter($query, 'date');

        $payments = $query->get();
        $this->result['status'] = 'success';
        $this->result['data'] = $payments->toArray();
        return $this->result();
    }

    public function client(Request $request, $id)
    {
        $company = Company::find($id);
        $companiesArray = [];
        if ($company) {
            $companiesArray = $company->affiliates->pluck('id')->toArray();
        }
        $companiesArray[] = (int) $id;

        $pikachu = '';
        $date = report_filter($pikachu, 'date');

        $endDate = new Carbon($date['date_to']);
        $endDate = $endDate->format('Y-m-d');

        $dateText = $request->interval == 'week' ? '53 - WEEK(date, 1) AS group_date' : "FLOOR((UNIX_TIMESTAMP('" . $endDate . "') - UNIX_TIMESTAMP(t.date)) / (60 * 60 * 24 * " . $request->interval . ")) AS group_date";

        $created = new Carbon($date['date_from']);
        $interval = $created->subDay()->diff($date['date_to'])->days;

        $query = DB::query()->fromSub(function ($query) use ($interval, $companiesArray) {
            $query->fromSub(function ($query) use ($interval, $companiesArray) {
                $union = DB::query()->from('contracts')
                    ->selectRaw('DATE_FORMAT(contracts.created_at, \'%Y-%m-%d\') AS date')
                    ->selectRaw('SUM(contracts.total) as total')
                    ->selectRaw('SUM(contracts.total - orders.partner_total) as profit')
                    ->leftJoin('orders', 'orders.id', '=', 'contracts.order_id')
                    ->whereIn('contracts.company_id', $companiesArray)
                    ->groupBy('date')
                    ->orderBy('date');
                $query->fromSub(function ($query) use ($interval) {
                    $query->selectRaw('DATE_FORMAT(DATE_ADD(NOW(), INTERVAL - (@d:= 0) DAY), \'%Y-%m-%d\') AS date, 0 AS total, 0 AS profit')
                        ->union(DB::query()->from('contracts')
                            ->selectRaw('DATE_FORMAT(DATE_ADD(NOW(), INTERVAL - (@d:= @d + 1) DAY), \'%Y-%m-%d\') AS date, 0 AS total, 0 AS profit')
                            ->limit($interval));
                }, 'tt')
                    ->union($union);
            }, 'ttt')
                ->selectRaw('ttt.date')
                ->selectRaw('SUM(ttt.total) as total')
                ->selectRaw('SUM(ttt.profit) as profit')
                ->groupByRaw('date');
        }, 't')
            ->groupByRaw('group_date')
            ->orderByRaw('group_date DESC')
            ->selectRaw('DATE_FORMAT(MAX(date), \'%d.%m.%Y\') AS date')
            ->selectRaw("SUM(t.total) AS total")
            ->selectRaw("SUM(t.total) / " . self::getDivider() . " AS total_without_nds")
            ->selectRaw("SUM(t.profit) AS profit")
            ->selectRaw($dateText);

        report_filter($query, 'date');

        $payments = $query->get();
        $this->result['status'] = 'success';
        $this->result['data'] = $payments->toArray();
        return $this->result();
    }


}
