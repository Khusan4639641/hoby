<?php

namespace App\Http\Controllers\Web\Panel;

use App\Helpers\LocaleHelper;
use App\Http\Controllers\Core\ReportFileController as Controller;
use App\Models\ReportFile;
use App\Helpers\FileHelper;
use App\Classes\Reports\ReportDispatcher;
use Exception;
use Facade\FlareClient\Report;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Log;
use App\Helpers\EncryptHelper;
use Illuminate\Support\Facades\DB;
use App\Jobs\CreateExport;

class ReportFileController extends Controller {

    /**
     * @param Collection | array $items
     * @return array
     */
    protected function formatDataTables($items) {

        $i = 0;
        $data = [];

        $report_types = ReportFile::getReportTypes();
        $report_states = ReportFile::getReportStates();
        $report_periods = ReportFile::getReportPeriods();

        //dd($report_periods);

        foreach ( $items as $item ) {

            $period = !is_null($item->period) ? $report_periods[$item->period] : "";
            $report = !is_null($item->report) ? $report_types[$item->report]['title'] : "";
            $start_date = !is_null($item->start_date) ? $item->start_date->format('d.m.Y') : "";
            $end_date = !is_null($item->end_date) ? $item->end_date->format('d.m.Y') : "";
            $term = $start_date && $end_date ? $start_date.' - '.$end_date : "";
            $state = !is_null($item->status) ? $report_states[$item->status] : "";

            if ($item->status && $item->status == ReportFile::STATUS_COMPLETE) {
                $file = '<a href="'.FileHelper::url($item->reportFile->path).'">Скачать<br></a><small>'.FileHelper::url($item->reportFile->path).'</small>';
            } else {
                $file = "";
            }

            /*if ($item->status && $item->status == ReportFile::STATUS_COMPLETE) {
                $file = '<a href="'.FileHelper::url($item->file).'">Скачать<br></a><small>'.FileHelper::url($item->file).'</small>';
            } else {
                $file = "";
            }*/

            $data[$i][] = '<div class="date">'.$item->id.'</div>';
            $data[$i][] = '<div class="inner">'.$report.'</div>';
            $data[$i][] = '<div class="date">'.$period.'</div>';
            $data[$i][] = '<div class="date">'.$term.'</div>';
            $data[$i][] = '<div class="inner">'.$item->created_at.'</div>';
            $data[$i][] = '<div class="date">'.$state.'</div>';
            $data[$i][] = '<div class="inner">'.$file.'</div>';
            /*$data[$i][] = '<button onclick="confirmDelete('.$item->id.')" type="button"
                                class="btn-delete">'.__('app.btn_delete').'</button>';*/
            $i++;
        }

        return parent::formatDataTables($data);
    }

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View
     */
    public function index()
    {
        $user = Auth::user();

        //if ($user->hasPermission('modify-news')) {
        if (true) {

            return view('panel.report_files.index', compact('user'));

        } else {

            $this->message('danger', __('app.err_access_denied'));
            return redirect(
                localeRoute('panel.index'))->with('message', $this->result['response']['message']
            );
        }
    }

    /**
     * Destroy region
     *
     * @param ReportFile $report_file
     * @return Application|RedirectResponse|Redirector
     * @throws Exception
     */
    public function destroy(ReportFile $report_file)
    {
        $result = $this->delete($report_file);

        return redirect(localeRoute( 'panel.report-files.index' ))->with('message', $result['response']['message']);
    }

    public function create() {

        $user = Auth::user();

        if ($user->hasRole('finance') || $user->hasRole('admin') ) {

            $report_types = ReportFile::getReportTypes();
            $report_periods = ReportFile::getReportPeriods();

            return view('panel.report_files.create', compact('report_types', 'report_periods'));
        }
    }

    public function export(Request $request)
    {
        /* $request: report, period, date */

        $dates = $this->prepareDates($request->date);

        $report_dispatcher = new ReportDispatcher(
            Auth::user()->id,
            $request->report,
            $request->period,
            $dates['start_date'],
            $dates['end_date']
        );

        $report_dispatcher->init();

        return redirect(localeRoute('panel.report-files.index'));
    }

    private function prepareDates($raw_date)
    {
        list(
            $dates['start_date'], $dates['end_date']
        ) = $raw_date ? explode(',', $raw_date) : [null,null];

        return $dates;
    }

    public function testExport() {

        /*
        1. Get request params: report, period, date
        2. Get current user and his id
        3. Generate appropriate name for export file based on report type and
            ReportFile::getReportTypes() array
        4. Create DB entries for report_files and files tables
        5. Initiate appropriate Export class handle function
        6. Export class handle function initiates DB query
        7. Export class create new empty file with appropriate name
        8. Export class chunks (by ~1000) the total result and creates
            a batch of jobs for appending them to file created
        9. On success, move created file to remote server
        10. Update the report_files table with success status
        */

        /*ProcessPodcast::withChain([
            new OptimizePodcast,
            new ReleasePodcast
        ])->dispatch();

        ProcessPodcast::withChain([
            new OptimizePodcast,
            new ReleasePodcast,
            function () {
                Podcast::update(...);
            },
        ])->dispatch();*/
    }
}
