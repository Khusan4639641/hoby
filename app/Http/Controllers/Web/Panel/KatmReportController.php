<?php

namespace App\Http\Controllers\Web\Panel;

use App\Facades\KATM\SaveKatm;
use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\GeneralCompany;
use App\Models\KatmReceivedReport;
use App\Models\KatmReport;
use Illuminate\Database\Eloquent\Builder;

class KatmReportController extends Controller
{

    private function getReportByNumber(Builder $query, $reportNumber): Builder
    {
        $reportNumberSuccess = $reportNumber . '_success';
        $reportNumberFail = $reportNumber . '_fail';
        $reportNumberAwait = $reportNumber . '_await';
        $reportNumberAll = $reportNumber . '_all';
        return $query
            ->selectRaw("SUM(IF (`status` = 1 AND report_number = '$reportNumber', 1, 0)) AS '$reportNumberSuccess'")
            ->selectRaw("SUM(IF ((`status` = 2 OR `status` = 3) AND report_number = '$reportNumber', 1, 0)) AS '$reportNumberFail'")
            ->selectRaw("SUM(IF (`status` = 0 AND report_number = '$reportNumber', 1, 0)) AS '$reportNumberAwait'")
            ->selectRaw("SUM(IF (report_number = '$reportNumber', 1, 0)) AS '$reportNumberAll'");
    }

    public function contractsSummaryPageView()
    {

        $query = KatmReport::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') AS 'date'")
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')");

        $query = $this->getReportByNumber($query, '001');
        $query = $this->getReportByNumber($query, 'start');
        $query = $this->getReportByNumber($query, '004');
        $query = $this->getReportByNumber($query, '005');
        $query = $this->getReportByNumber($query, '015');
        $query = $this->getReportByNumber($query, '016');
        $query = $this->getReportByNumber($query, '018');
        $katmReports = $query->get();
        return view('panel.katm-report.report.contracts',
            compact('katmReports'));
    }

    public function contractsPageView()
    {
        $contracts = Contract::query()
            ->withCount('katmReport')
            ->whereHas('katmReport')
            ->whereHas('generalCompany', function ($query) {
                $query->where('type', GeneralCompany::TYPE_MFI);
            })
            ->paginate();
        return view('panel.katm-report.contracts', compact('contracts'));
    }

    public function showPageView($contractID)
    {
        $contract = Contract::find($contractID);
        $reports = KatmReport::query()
            ->where('contract_id', $contractID)
            ->orderBy('order')
            ->get();
        $receiveReports = KatmReceivedReport::query()
            ->where('contract_id', $contractID)
            ->get();
        return view('panel.katm-report.show', compact('contract', 'reports', 'receiveReports'));
    }

    public function showSendingReportPageView($reportID)
    {
        $report = KatmReport::find($reportID);
        return view('panel.katm-report.send.show', compact('report'));
    }

    public function showReceivingReportPageView($reportID)
    {
        $report = KatmReceivedReport::find($reportID);
        $jsonReport = SaveKatm::findReportText($report->contract, KatmReceivedReport::TYPE_START, $report->token);
        return view('panel.katm-report.receive.show', compact('report', 'jsonReport'));
    }


    public function showReportView($reportID)
    {
        $report = KatmReceivedReport::find($reportID);
        $jsonReport = SaveKatm::findReport($report->contract, KatmReceivedReport::TYPE_START, $report->token);
        return view('panel.buyer.katm', ['report' => $jsonReport['report']]);
    }

}
