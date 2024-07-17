<?php

namespace App\Http\Controllers\Web\Billing;

use App\Exports\DelaysForEachPartnerExport;
use App\Exports\FilialsCancelExport;
use App\Exports\FilialsExport;
use App\Exports\VendorsCancelExport;
use App\Exports\VendorsExport;
use App\Http\Controllers\Core\ReportsController as Controller;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel;
use Illuminate\Support\Facades\Auth;

class ReportsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\View\View
     */


    private $excel;

    public function __construct(Excel $excel)
    {
        $this->excel = $excel;
    }

    public function index()
    {
        $user = Auth::user();
        $result = $this->info();
        if ($result['status'] != 'success' && $result['response']['code'] == 403) {
            $this->message('danger', __('app.err_access_denied'));
            return redirect(localeRoute('billing.index'))->with('message', $this->result['response']['message']);
        } else {

            if ($user->hasRole('finance') || $user->hasRole('admin') ) {


                return view('billing.reports.index',
                    [/*'title1' => 'Бухгалтерия',
                        'title2' => 'Списания',
                        'title3' => 'Пополнения',
                        'title4' => 'Верификация',
                        'title5' => 'Договора',
                        'title6' => 'Просрочка',
                        'title7' => 'Вендора',
                        'access' => 'sales_finance',
                        'model' => 'orders',
                        'model2' => 'payments',
                        'model3' => 'history',
                        'model4' => 'verified',
                        'model5' => 'contracts',
                        'model6' => 'delays',*/
                        'model7' => 'vendors',
                        'model8' => 'filials',
                        //'model9' => 'filials-cancel',
                    ]);
            }

        }
        return;
    }

    public function export(Request $request, $model)
    {
        if ($model == 'delays') {
            $company_id         = $request->company_id          ?? null;
            $company_parent_id  = $request->company_parent_id   ?? null;
            $user_id            = $request->user_id             ?? null;

            return $this->excel->download(new DelaysForEachPartnerExport($company_id, $company_parent_id, $user_id), 'Отчёт по просрочникам.xlsx');
        }

        if ($model == 'filials') {
            return $this->excel->download(new FilialsExport, 'filials.xlsx');
        }

        if ($model == 'filialsCancel') {
            return $this->excel->download(new FilialsCancelExport($request), 'FilialsCancel.xlsx');
        }

        if ($model == 'vendors') {
            return $this->excel->download(new VendorsExport, 'vendors.xlsx');
        }

        if ($model == 'vendorsCancel') {

            $result = VendorsCancelExport::report($request);
            $f = fopen('php://memory', md5(microtime()));
            fseek($f, 0);
            fputs($f, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($f, $result['header'], ";");
            foreach ($result['values'] as $res) {
                $res[3] = " ".$res[3]." ";
                $res[4] = " ".$res[4]." ";
                fputcsv($f, $res , ";");
            }
            fseek($f, 0);
            header('Content-Disposition: attachment; filename="Отчет по вендорам c отмененными.csv";');
            fpassthru($f);
            exit;
        }
    }
}

?>
