<?php

namespace App\Http\Controllers\Web\Panel;

use App\Exports\BonusClientsExport;
use App\Exports\BonusExport;
use App\Exports\ComparativeDocumentExport;
use App\Exports\ContractsCancelExport;
use App\Exports\ContractsExport;
use App\Exports\DebtCollectorsFilteredExport;
use App\Exports\DebtorsExport;
use App\Exports\DelayExExport;
use App\Exports\DelayKycExport;
use App\Exports\DetailedContractsExport;
use App\Exports\EdTransactionExport;
use App\Exports\FilesHistorylExport;
use App\Exports\HistoryExport;
use App\Exports\OrdersCancelExport;
use App\Exports\OrdersCancelNewExport;
use App\Exports\OrdersExport;
use App\Exports\PaymentDateExport;
use App\Exports\PaymentFillExport;
use App\Exports\PaymentsExport;
use App\Exports\ReverseBalanceExport;
use App\Exports\TransactionsExport;
use App\Exports\VendorsFillialExport;
use App\Exports\VendorsFullExport;
use App\Exports\VerifiedExport;
use App\Http\Controllers\Core\ReportsController as Controller;
use App\Http\Requests\Web\Panel\ReportsController\DebtCollectorsFilteredExportRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Excel;

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

            if ($user->hasRole('finance') || $user->hasRole('admin')) {
                return view('panel.reports.index',
                    [
                        'title1' => 'Бухгалтерия',
                        'title2' => 'Списания',
                        'title3' => 'Пополнения',
                        'title4' => 'Верификация',
                        'title5' => 'Договора',
                        'title6' => 'Просрочка',
                        'title7' => 'Просрочка расширенная',
                        'access' => 'sales_finance',
                        'reports' => [
                            [
                                'name' => 'orders',
                                'title' => 'Бухгалтерия',
                            ],
                            [
                                'name' => 'ordersCancel',
                                'title' => 'Бухгалтерия c отмененными',
                            ],
                            [
                                'name' => 'ordersCancelNew',
                                'title' => 'Бухгалтерия c отмененными (новая)',
                            ],
                            [
                                'name' => 'payments',
                                'title' => 'Списания',
                            ],
                            [
                                'name' => 'history',
                                'title' => 'Пополнения',
                            ],
                            [
                                'name' => 'transactions',
                                'title' => 'Транзакции',
                            ],
                            [
                                'name' => 'paymentFill',
                                'title' => 'Пополнения/Списания',
                            ],
                            [
                                'name' => 'contracts',
                                'title' => 'Договора',
                            ],
                            [
                                'name' => 'delays',
                                'title' => 'Просрочка',
                            ],
                            [
                                'name' => 'delaysEx',
                                'title' => 'Просрочка расширенная',
                            ],
                            [
                                'name' => 'paymentDate',
                                'title' => 'Дата погашения',
                            ],
                            [
                                'name' => 'vendorsFull',
                                'title' => 'Общий отчет по продажам',
                            ],
                            [
                                'name' => 'vendorsFillial',
                                'title' => 'Детальный отчет по продажам',
                            ],
                            [
                                'name' => 'bonus',
                                'title' => 'Начисление бонусов (контракты)',
                            ],
                            [
                                'name' => 'bonusClients',
                                'title' => 'Начисление бонусов (клиенты)',
                            ],
                            [
                                'name' => 'detailedContracts',
                                'title' => 'Детальные договора',
                            ],
                            [
                                'name' => 'debtors',
                                'title' => 'Задолжники больше 60 дней',
                            ],
                            [
                                'name' => 'edTransaction',
                                'title' => "Отчет по эд"
                            ],
                            [
                                'name' => 'comparativeDocument',
                                'title' => "СОЛИШТИРМА ДАЛОЛАТНОМА",
                                'input'=>['type'=>'number']
                            ],
                            [
                                'name' => 'reverseBalance',
                                'title' => "Оборотно-сальдовая ведомость"
                            ],
                        ]
                    ]);
            }
        }
    }

    public function commercialIndex()
    {
        return view('panel.reports.index',
            [
                'access' => 'sales_finance',
                'reports' => [
                    [
                        'name' => 'vendorsFull',
                        'title' => 'Общий отчет по продажам',
                    ],
                    [
                        'name' => 'vendorsFillial',
                        'title' => 'Детальный отчет по продажам',
                    ],
                ]
            ]);
    }

    public function export(Request $request, $model)
    {
        \Log::info($request->all());
        \Log::info($model);
        $period = $request->get('type', 'last_day');
        if ($period == 'last_day') {
            $date_from = Carbon::today()->startOfDay();
            $date_to = Carbon::today()->endOfDay();
        } elseif ($period == 'last_week') {
            $date_from = Carbon::today()->subDays(7)->startOfDay();
            $date_to = Carbon::today()->endOfDay();
        } elseif ($period == 'last_month') {
            $date_from = Carbon::today()->subMonth()->startOfDay();
            $date_to = Carbon::today()->endOfDay();
        } elseif ($period == 'last_half_year') {
            $date_from = Carbon::today()->subMonths(6)->startOfDay();
            $date_to = Carbon::today()->endOfDay();
        } elseif ($period == 'custom') {
            [$start, $end] = explode(',', $request->get('date'));
            $date_from = Carbon::createFromFormat('d.m.Y', $start)->endOfDay();
            $date_to = Carbon::createFromFormat('d.m.Y', $end)->endOfDay();
        }

        switch ($model) {
            case 'orders':
                return $this->excel->download(new OrdersExport, 'orders.xlsx'); // Бухгалтерия
            case 'ordersCancelNew':
                return $this->excel->download(new OrdersCancelNewExport, 'orders_cancel_new.xlsx'); // Бухгалтерия c отмененными (новая)
            case 'payments':
                return $this->excel->download(new PaymentsExport, 'payments.xlsx'); // Списания
            case 'transactions':
                return $this->excel->download(new TransactionsExport, 'transactions.xlsx'); // Транзакции
            case 'history':
                return $this->excel->download(new HistoryExport, 'history.xlsx'); // Пополнения
            case 'verified':
                return $this->excel->download(new VerifiedExport, 'verified.xlsx'); // нету в списке выбора в Админке
            case 'contracts':
                return $this->excel->download(new ContractsExport, 'contracts.xlsx'); // Договора
            case 'detailedContracts':
                return $this->excel->download(new DetailedContractsExport, 'detailed-contracts.xlsx'); // Детальные договора
            case 'delays':
                return $this->excel->download(new DelayKycExport, 'delays.xlsx'); // Просрочка
            case 'delaysEx':
                return $this->excel->download(new DelayExExport, 'delaysEx.xlsx'); // Просрочка расширенная
            case 'paymentDate':
                return $this->excel->download(new PaymentDateExport, 'paymentDate.xlsx'); // Дата погашения
            case 'bonus':
                return $this->excel->download(new BonusExport, 'bonus.xlsx'); // Начисление бонусов (контракты)
            case 'bonusClients':
                return $this->excel->download(new BonusClientsExport, 'bonus-clients.xlsx'); // Начисление бонусов (клиенты)
            case 'debtors':
                return $this->excel->download(new DebtorsExport, 'debtors.xlsx');
            case 'edTransaction':
                return $this->excel->download(new EdTransactionExport(
                    $date_from,
                    $date_to,
                    ''
                ), 'TransactionExport.xlsx');
            case 'comparativeDocument':
                return $this->excel->download(new ComparativeDocumentExport(
                    $date_from,
                    $date_to,
                    $request->inn
                ), 'ComparativeDocumentExport.xlsx');
            case 'reverseBalance':
                return $this->excel->download(new ReverseBalanceExport(
                    $date_from,
                    $date_to,
                    ''
                ), 'ReverseBalance.xlsx');
        }

        if ($model == 'ordersCancel') {

            $result = OrdersCancelExport::report();

            $filename = 'orders_cancel.csv';
            $file_catalog = iconv('utf-8', 'windows-1251//TRANSLIT', $result);

            file_put_contents($filename, $file_catalog);

            if (file_exists($filename)) {

                header('Content-type: ' . mime_content_type($filename));
                header('Content-Disposition: attachment; filename=' . $filename);
                readfile($filename);
                exit;

            } else {
                return redirect('404');
            }

        } // + "Бухгалтерия c отмененными"

        if ($model == 'paymentFill') { // оплата / пополнение
            $result = PaymentFillExport::report();

            $filename = 'payment_fill.csv';
            $file_catalog = iconv('utf-8', 'windows-1251//TRANSLIT', $result);

            file_put_contents($filename, $file_catalog);

            if (file_exists($filename)) {

                header('Content-type: ' . mime_content_type($filename));
                header('Content-Disposition: attachment; filename=' . $filename);
                readfile($filename);
                exit;

            } else {
                return redirect('404');
            }
        } // - "Пополнения/Списания"

        if ($model == 'contractsCancel') {


            $result = ContractsCancelExport::report();

            $filename = 'contract_cancel.csv';
            $file_catalog = iconv('utf-8', 'windows-1251//TRANSLIT', $result);

            file_put_contents($filename, $file_catalog);

            if (file_exists($filename)) {
                header('Content-type: ' . mime_content_type($filename));
                header('Content-Disposition: attachment; filename=' . $filename);
                readfile($filename);
                exit;
            } else {
                return redirect('404');
            }

        } // - "" нету в списке выбора в Админке

        if ($model == 'vendorsFull') {

            $result = VendorsFullExport::report();

            $filename = 'vendorFull.csv';
            $file_catalog = iconv('utf-8', 'windows-1251//TRANSLIT', $result);

            file_put_contents($filename, $file_catalog);

            if (file_exists($filename)) {
                header('Content-type: ' . mime_content_type($filename));
                header('Content-Disposition: attachment; filename=' . $filename);
                readfile($filename);
                exit;
            } else {
                return redirect('404');
            }
        } // + "Общий отчет по продажам"

        if ($model == 'vendorsFillial') {
            //return $this->excel->download(new VendorsFillialExport, 'vendorsFillial.xlsx');

            $result = VendorsFillialExport::report();

            $filename = 'vendorFillial.csv';
            $file_catalog = iconv('utf-8', 'windows-1251//TRANSLIT', $result);

            file_put_contents($filename, $file_catalog);

            if (file_exists($filename)) {
                header('Content-type: ' . mime_content_type($filename));
                header('Content-Disposition: attachment; filename=' . $filename);
                readfile($filename);
                exit;
            } else {
                return redirect('404');
            }
        }

        if ($model = 'filesHistory') {
            $FilesHistory = FilesHistorylExport::report($request);
            $size = count($FilesHistory);
            $FilesHistory = array_chunk($FilesHistory, 100000);
            return view('panel.recovery.parts.exports_files_history', compact('FilesHistory', 'size'));
        }
    }

    public function debtCollectorsFilteredExport(DebtCollectorsFilteredExportRequest $request)
    {
        return $this->excel->download(
            new DebtCollectorsFilteredExport($request->recovery, $request->contract_date_from, $request->contract_date_to, $request->delay_days_from, $request->delay_days_to, $request->contract_balance_from, $request->contract_balance_to, $request->katm_region),
            'filtered-export.xlsx',
        );
    }

}

?>
