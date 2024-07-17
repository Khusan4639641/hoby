<?php

namespace App\Http\Controllers\Core;

use App\Http\Requests\FromMkoReportListRequest;
use App\Jobs\MkoJobs\MkoReportJob;
use App\Helpers\{EncryptHelper, NdsStopgagHelper};
use App\Http\Requests\FromMkoReportRequest;
use App\Models\{MkoReport, Order, Payment};
use App\Services\Web\Panel\MkoToBankReportsService;
use App\Exports\FilesHistorylExport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Log, Validator};

class ReportsController extends CoreController
{

    public function orders1(Request $request)
    {
        $request->merge(['general_company_id' => 1]);
        return $this->orders($request);
    }

    public function orders2(Request $request)
    {
        $request->merge(['general_company_id' => 2]);
        return $this->orders($request);
    }

    /**
     * @param array $params
     *
     * @return array|false|string
     */
    private function orders(Request $request)
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {

            $genComID = $request->general_company_id;
            $errors = [];
            $result = [];


            if (isset($request->date_from)) {
                $date_from = date('Y.m.d H:i:s', strtotime($request->date_from . ' 00:00:00'));
            } else {
                if(isset($request->date_from_time)){
                    $date_from = date('Y.m.d H:i:s', strtotime($request->date_from_time));
                }else{
                    $errors[] = __('date_from_not_found');
                }

            }

            if (isset($request->date_to)) {
                $date_to = date('Y.m.d H:i:s', strtotime($request->date_to . ' 23:59:59'));
            } else {
                if(isset($request->date_to_time)){
                    $date_to = date('Y.m.d H:i:s', strtotime($request->date_to_time));
                }else{
                    $errors[] = __('date_to_not_found');
                }
            }

            if ($errors) {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->result['response']['errors'] = $errors;
                return $this->result();
            }

            $orders_query = Order::with('contractIn')
                ->with('company')
                ->with('products')
                ->whereHas('contract', function ($query) use ($genComID) {
                    $query->where('general_company_id', $genComID);
                })
                ->has('company')
                ->has('products')
                ->where('test', 0)  // тестовые не отправляем
                ->where('status', 9) //
                ->whereBetween('created_at', [$date_from, $date_to]);


            if ($orders = $orders_query->get()) {


                // return $orders;

                foreach ($orders as $order) {

                    if (in_array($order->contract->status, [0, 2])) continue; // пропустить договора в обработке

                    //$date = new DateTime($order->dateFull);
                    // if ($order->contract->confirmation_code !== "" ) { //&& ($date >= $date_from && $date <= $date_to)) {

                    if (isset($order->partnerSettings) && $order->partnerSettings->nds) {
                        $nds = NdsStopgagHelper::getActualNdsPlusOne($order->created_at);
                        $nds_label = NdsStopgagHelper::getActualNdsValue($order->created_at) . '%';
                    } else {
                        $nds = 1;
                        $nds_label = '0%';
                    }

                    $products = [];
                    foreach ($order->products as $key => $product) {

                        //$product_nds = $product->price / $nds + $product->price;
                        //$product_discount_nds = $product->price_discount / $nds + $product->price_discount;

                        $priceWithNdsProd = number_format($product->price * $product->amount, 2, ".", "");
                        $priceWithoutNdsProd = number_format($priceWithNdsProd / NdsStopgagHelper::getActualNdsPlusOne($order->created_at), 2, ".", "");

                        $priceWithoutNdsZakup = number_format(round($product->price_discount / NdsStopgagHelper::getActualNdsPlusOne($order->created_at)), 2, ".", "");

                        if ($product->imei == '' || is_null($product->imei)) {
                            $product_name = $product->name;
                        } else {
                            $product_name = $product->category_id == 1 ? mb_substr($product->name, 0, -22) : $product->name;
                        }

                        // для закупа
                        $products[1][$key] = array(
                            "IDТовара" => $product->id,
                            "Наименование_товара" => $product_name, // $product->name,
                            "ЕдиницаИзмерения" => "Штука",
                            "Количество" => floatval(number_format($product->amount, 2, ".", "")),
                            "Цена" => $priceWithoutNdsZakup,
                            "Сумма" => number_format($priceWithoutNdsZakup * $product->amount, 2, ".", ""), //floatval(number_format($product->withoutNdsDiscount * $product->amount, 2, ".", "")),
                            "СтавкаНДС" => $nds_label,
                            "СуммаНДС" => number_format($product->price_discount * $product->amount - $product->price_discount / $nds * $product->amount, 2, '.', ''),
                            "СуммаCНДС" => number_format($product->price_discount * $product->amount, 2, '.', ''),
                        );

                        // для продажи
                        $products[2][$key] = array(
                            "IDТовара" => $product->id,
                            "Наименование_товара" => $product_name, // $product->name,
                            "ЕдиницаИзмерения" => "Штука",
                            "Количество" => floatval(number_format($product->amount, 2, ".", "")),
                            "Цена" => number_format($priceWithoutNdsProd / $product->amount, 2, '.', ''),
                            "Сумма" => $priceWithoutNdsProd,
                            "СтавкаНДС" => NdsStopgagHelper::getActualNdsValue($order->created_at) . '%',
                            "СуммаНДС" => number_format($priceWithNdsProd - $priceWithoutNdsProd, 2, '.', ''),//number_format($product->price * $product->amount - $product->price / 1.15 * $product->amount ,2,'.',''), // $product_nds - $product->price, //floatval(number_format($product->price - $product_nds, 2, ".", "")),
                            "СуммаCНДС" => $priceWithNdsProd,
                        );

                    }

                    $result["Закуп"][] = array(
                        "IDОферты" => $order->id, //  $order->contract->id,
                        "НомерОферты" => $order->contract->id,
                        "ДатаОферты" => strtotime($order->contract->created_at), // dateFull
                        "ДатаСчетФактуры" => strtotime($order->created_at), // dateFull
                        "НомерСчетФактуры" => $order->contract->id,
                        "IDПродавца" => $order->company->id,
                        "ИннПоставщика" => $order->company->inn,
                        "НаименованиеПоставщика" => $order->company->name,
                        "IDМенеджераКонтракта" => $order->company->manager_id,
                        "Товары" => $products[1],
                    );

                    $collection = collect($order->contract->schedule);

                    $paymentGraph = $collection->map(function ($item) {
                        return array(
                            "ДатаПлатежа" => strtotime($item['payment_date']),
                            "СуммаПлатежа" => $item['total'], // $item['price'],
                        );
                    });
                    // если есть депозит, добавляем как первый платеж
                    if ($order->contract->deposit > 0) {
                        $paymentGraph->prepend([
                            "ДатаПлатежа" => strtotime($order->contract->created_at),
                            "СуммаПлатежа" => $order->contract->deposit,
                        ]);
                    }

                    $result["Продажа"][] = [
                        "IDОферты" => $order->id,
                        "IDОфертыЗакуп" => $order->contract->id,
                        "НомерОферты" => $order->contract->id,
                        "ДатаОферты" => strtotime($order->contract->created_at), //dateFull
                        "ДатаСчетФактуры" => strtotime($order->created_at), //dateFull
                        "НомерСчетФактуры" => $order->contract->id,
                        "IDПокупателя" => $order->buyer->user->id,
                        "ИНН_Покупателя" => $order->buyer->personals->innNoCrypt,
                        "ПаспортПокупателя" => EncryptHelper::decryptData($order->buyer->personals->passport_number),
                        "ПИНФЛ_Покупателя" => EncryptHelper::decryptData($order->buyer->personals->pinfl),
                        "ТелефонПокупателя" => $order->buyer->phone,
                        "НаименованиеПокупателя" => $order->buyer->user->fio,
                        "АдресПокупателя" => @$order->buyer->addressRegistration->address,
                        "Товары" => $products[2],
                        "ГрафикПлатежей" => $paymentGraph
                    ];

                    // } // ->contract


                }

                $this->result['status'] = 'success';
                $this->result['response']['code'] = 200;
                $this->result['data'] = $result;

                return $this->result();


            }

            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            //$this->result['response']['message'] = __('');
            $this->result['response']['errors'] = 'Contracts ' . __('app.err_not_found');

            return $this->result();


        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 403;
            //$this->result['response']['message'] = __('');

            return $this->result();
        }
    }


    /**
     * @param array $params
     *
     * @return array|false|string
     */
    public function history(Request $request)
    {
        $user = Auth::user();

        if ($user->hasRole('admin')) {


            $errors = [];
            $result = [];


            if (isset($request->date_from)) {
                $date_from = date('Y.m.d H:i:s', strtotime($request->date_from . ' 00:00:00'));
            } else {
                if(isset($request->date_from_time)){
                    $date_from = date('Y.m.d H:i:s', strtotime($request->date_from_time));
                }else{
                    $errors[] = __('date_from_not_found');
                }

            }


            if (isset($request->date_to)) {
                $date_to = date('Y.m.d H:i:s', strtotime($request->date_to . ' 23:59:59'));
            } else {
                if(isset($request->date_to_time)){
                    $date_to = date('Y.m.d H:i:s', strtotime($request->date_to_time));
                }else{
                    $errors[] = __('date_to_not_found');
                }
            }


            if ($errors) {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->result['response']['errors'] = $errors;

                return $this->result();
            }

            if ($payments = Payment::where(function ($query) {
                $query->where(function ($query) {
                    $query->where('type', 'user')
                        ->where('status', 1); // auto, insurance, supplier
                })
                    ->orWhere(function ($query) {
                        $query->whereHas('contract', function (Builder $query) {
                            $query->where('status', '=', '5');
                        });
                    });
            })
                ->with('buyer')
                ->has('buyer')
                ->with('contract')
                ->whereBetween('created_at', [$date_from, $date_to])
                ->get()) {

                foreach ($payments as $payment) {
                    $status = $payment->status == 1 ? 'Оплачена' : 'Не оплачена';
                    if ($payment->contract) {
                        if ($payment->contract->status == 5 && ($payment->status == 1 || $payment->status == 11)) {
                            $status = 'Оплачена';
                        }
                    }
                    $result["Пополнения"][] = array(
                        "IDЗаписи" => $payment->id,
                        "IDПокупателя" => $payment->buyer->user->id,
                        "ИННПокупателя" => $payment->buyer->personals ? intval(EncryptHelper::decryptData($payment->buyer->personals->inn)) : null,
                        "НаименованиеПокупателя" => $payment->buyer->user->fio,
                        "АдресПокупателя" => $payment->buyer->addressRegistration ? @$payment->buyer->addressRegistration->address : null,
                        "ДатаСоздания" => strtotime($payment->created_at), // dateFull
                        "СуммаПополнения" => $payment->amount,
                        "ПлатежнаяСистема" => $payment->payment_system,
                        "Статус" => $status,
                    );
                }

                $this->result['status'] = 'success';
                $this->result['response']['code'] = 200;
                $this->result['data'] = $result;

                return $this->result();
            }

            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->result['response']['errors'] = 'Payments ' . __('app.err_not_found');

            return $this->result();

        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 403;

            return $this->result();
        }
    }


    public function payments(Request $request)
    {

        $user = Auth::user();

        if ($user->hasRole('admin')) {

            $errors = [];
            $result = [];

            if (isset($request->date_from)) {
                $date_from = date('Y.m.d H:i:s', strtotime($request->date_from . ' 00:00:00'));
            } else {
                if(isset($request->date_from_time)){
                    $date_from = date('Y.m.d H:i:s', strtotime($request->date_from_time));
                }else{
                    $errors[] = __('date_from_not_found');
                }
            }

            if (isset($request->date_to)) {
                $date_to = date('Y.m.d H:i:s', strtotime($request->date_to . ' 23:59:59'));
            } else {
                if(isset($request->date_to_time)){
                    $date_to = date('Y.m.d H:i:s', strtotime($request->date_to_time));
                }else{
                    $errors[] = __('date_to_not_found');
                }
            }

            if ($errors) {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->result['response']['errors'] = $errors;
                return $this->result();
            }

            if ($payments = Payment::with('contract')
                ->with('buyer')
                ->has('order')
                ->whereHas('contract', function (Builder $query) {
                    $query->where('status', '!=', '5');
                })
                ->has('buyer')
                ->whereIn('type', ['auto', 'refund'])
                ->whereHas('order', function ($q) {
                    $q->where('test', 0);   // тестовые не берем
                })
                ->whereBetween('created_at', [$date_from, $date_to])
                ->get()) {

                foreach ($payments as $payment) {

                    $result["Списания"][] = array(
                        "IDЗаписи" => $payment->id,
                        "IDОферты" => $payment->order_id, // 19.07  contract->id,
                        "IDПокупателя" => $payment->buyer->user->id,
                        "ИННПокупателя" => intval(EncryptHelper::decryptData($payment->buyer->personals->inn)),
                        "НаименованиеПокупателя" => $payment->buyer->user->fio,
                        "АдресПокупателя" => @$payment->buyer->addressRegistration->address,
                        "ДатаСоздания" => strtotime($payment->created_at),
                        "СуммаСписания" => number_format($payment->amount, 2, '.', ''),
                        "ПлатежнаяСистема" => $payment->payment_system,
                        "Статус" => $payment->status == 0 ? 'Не оплачена' : 'Оплачена',
                    );

                }

                $this->result['status'] = 'success';
                $this->result['response']['code'] = 200;
                $this->result['data'] = $result;

                return $this->result();

            }


            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->result['response']['errors'] = 'Payments ' . __('app.err_not_found');

            return $this->result();

        } else {

            $this->result['status'] = 'error';
            $this->result['response']['code'] = 403;
            //$this->result['response']['message'] = __('');

            return $this->result();
        }
    }

    public function canceledContracts1(Request $request)
    {
        $request->merge(['general_company_id' => 1]);
        return $this->canceledContracts($request);
    }

    public function canceledContracts2(Request $request)
    {
        $request->merge(['general_company_id' => 2]);
        return $this->canceledContracts($request);
    }

    private function canceledContracts(Request $request)
    {
        $user = Auth::user();
        if ($user->hasRole('admin')) {

            $genComID = $request->general_company_id;
            $errors = [];
            $result = [];

            if (isset($request->date_from)) {
                $date_from = date('Y.m.d H:i:s', strtotime($request->date_from . ' 00:00:00'));
            } else {
                if(isset($request->date_from_time)){
                    $date_from = date('Y.m.d H:i:s', strtotime($request->date_from_time));
                }else{
                    $errors[] = __('date_from_not_found');
                }
            }

            if (isset($request->date_to)) {
                $date_to = date('Y.m.d H:i:s', strtotime($request->date_to . ' 23:59:59'));
            } else {
                if(isset($request->date_to_time)){
                    $date_to = date('Y.m.d H:i:s', strtotime($request->date_to_time));
                }else{
                    $errors[] = __('date_to_not_found');
                }
            }

            if ($errors) {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->result['response']['errors'] = $errors;
                return $this->result();
            }

            if ($orders = Order::with('contract')
                ->where('status', 5)
                ->where('test', 0)  // тестовые не отправляем
                ->whereHas('contract', function ($query) use ($date_from, $date_to, $genComID) {
                    $query->whereBetween('canceled_at', [$date_from, $date_to]);
                    $query->where('general_company_id', $genComID);
                })
                ->get()) {

                foreach ($orders as $order) {
                    $result["Отмены"][] = array(
                        "IDДоговора" => $order->id,
                        "ДатаОтмены" => strtotime($order->contract->canceled_at),
                    );
                }
                $this->result['status'] = 'success';
                $this->result['response']['code'] = 200;
                $this->result['data'] = $result;

                return $this->result();

            }

            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            //$this->result['response']['message'] = __('');
            $this->result['response']['errors'] = 'Payments ' . __('app.err_not_found');

            return $this->result();


        } else {

            $this->result['status'] = 'error';
            $this->result['response']['code'] = 403;
            //$this->result['response']['message'] = __('');

            return $this->result();

        }
    }

    public function bonuses(Request $request)
    {
        $user = Auth::user();
        if ($user->hasRole('admin')) {

            $validator = Validator::make($request->all(), [
                'date_from' => ['required'],
                'date_to' => ['required'],
            ], [
                'date_from.required' => __('date_from_not_found'),
                'date_to.required' => __('date_to_not_found'),
            ]);

            if(!isset($request->date_from_time)){
                if ($validator->fails()) {
                    $this->result['status'] = 'error';
                    $this->result['response']['code'] = 404;
                    $this->result['response']['errors'] = $validator->errors();
                    return $this->result();
                }else{
                    $date_from = date('Y-m-d', strtotime($request->date_from));
                    $date_to = date('Y-m-d', strtotime($request->date_to));
                }
            }else{
                $date_from = date('Y-m-d H:i:s', strtotime($request->date_from_time));
                $date_to = date('Y-m-d H:i:s', strtotime($request->date_to_time));
            }

            $sellerPinflTitle = __('ПИНФЛПродавца');
            $sellerPassportTitle = __('ПаспортныеДанныеПродавца');

            $payments = DB::table('payments')
                ->leftJoin('seller_bonuses', 'seller_bonuses.payment_id', '=', 'payments.id')
                ->leftJoin('users', 'payments.user_id', '=', 'users.id')
                ->leftJoin('buyer_personals', 'buyer_personals.user_id', '=', 'users.id')
                ->leftJoin('companies', 'users.seller_company_id', '=', 'companies.id')
                ->leftJoin('contracts', 'seller_bonuses.contract_id', '=', 'contracts.id')
                ->leftJoin('orders', 'contracts.order_id', '=', 'orders.id')
                ->where('payments.payment_system', '=', Payment::PAYMENT_SYSTEM_PAYCOIN)
                ->where('payments.status', '=', Payment::PAYMENT_STATUS_ACTIVE)
                //->whereRaw('DATE(payments.created_at) BETWEEN \'' . date('Y-m-d', strtotime($request->date_from)) . '\' AND \'' . date('Y-m-d', strtotime($request->date_to)) . '\'')
                ->whereRaw('DATE(payments.created_at) BETWEEN \'' . $date_from . '\' AND \'' . $date_to . '\'')
                ->selectRaw('companies.id AS ' . __('IDКомпании') . '')
                ->selectRaw('companies.name AS ' . __('Компания') . '')
                ->selectRaw('companies.brand AS ' . __('БрендКомпании') . '')
                ->selectRaw('users.id AS ' . __('IDПродавца') . '')
                ->selectRaw('CONCAT(users.name, \' \', users.surname, \' \', users.patronymic) AS ' . __('Продавец') . '')
                ->selectRaw('buyer_personals.pinfl AS ' . $sellerPinflTitle . '')
                ->selectRaw('buyer_personals.passport_number AS ' . $sellerPassportTitle . '')
                ->selectRaw('contracts.id AS ' . __('IDКонтракта') . '')
                ->selectRaw('CAST(orders.partner_total AS DECIMAL(12, 2)) AS ' . __('СуммаТовара') . '')
                ->selectRaw('seller_bonuses.coefficient AS ' . __('Коэффициент') . '')
                ->selectRaw('CASE WHEN payments.type = \'' . Payment::PAYMENT_TYPE_FILL_ACCOUNT . '\' THEN \'' . __('Начисление') . '\' WHEN payments.type = \'' . Payment::PAYMENT_TYPE_PAY . '\' THEN \'' . __('Расход') . '\' WHEN payments.type = \'' . Payment::PAYMENT_TYPE_REFUND . '\' THEN \'' . __('Возврат') . '\' ELSE \'\' END AS ' . __('Тип') . '')
                ->selectRaw('IFNULL(payments.amount, 0) AS ' . __('Сумма') . '')
                ->selectRaw('payments.created_at AS ' . __('ДатаОперации') . '')
                ->orderByRaw('payments.created_at, contracts.id')
                ->get()
            ;

//            dd($payments->toSql());

            $paymentsArr = $payments;

            foreach ($paymentsArr as $key => $paymentsItem) {
                $paymentsArr[$key]->$sellerPinflTitle = EncryptHelper::decryptData($paymentsItem->$sellerPinflTitle);
                $paymentsArr[$key]->$sellerPassportTitle = EncryptHelper::decryptData($paymentsItem->$sellerPassportTitle);
            }

            $result = $paymentsArr;

            $this->result['status'] = 'success';
            $this->result['response']['code'] = 200;
            $this->result['data'] = $result;

            return $this->result();


        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 403;
            return $this->result();
        }
    }

    public function info($params = [])
    {
        $user = Auth::user();
        if ($user->hasRole('finance') || $user->hasRole('admin')) {
            $data = '';
            $this->result['status'] = 'success';
            $this->result['data'] = $data;
        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 403;
            $this->message('danger', __('app.err_access_denied'));
        }
        return $this->result();
    }

    public function access()
    {
        $user = Auth::user();
//        || $user->hasRole('sales')
        if ($user->hasRole('finance') || $user->hasRole('admin')) {
            return view('panel.reports.index',
                ['title1' => 'Бухгалтерия',
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
                    'model6' => 'delays',
                    // 'model7' => 'vendors'
                ]);
        }
    }

    public function filesHistory(Request $request) {
        $FilesHistory = new FilesHistorylExport();
            $report = $FilesHistory->report($request);
            return $report;
    }

    /**
     * Отчет "Пакет от МКО для ИХБС"
     *
     * @param FromMkoReportRequest $request
     *
     * @return array
     */
    public function fromMko(FromMkoReportRequest $request): array
    {
        MkoReportJob::dispatch($request->all())
            ->onConnection('redis_mko_report');
        $this->result['status'] = 'success';

        return $this->result();
    }

    /**
     * Получить все записи отчетов в ЦБ с фильтрацией
     *
     * @param FromMkoReportListRequest $request
     *
     * @return array
     */
    public function getMkoReportsList(FromMkoReportListRequest $request): array
    {
        $result = MkoToBankReportsService::getMkoReportsList($request);

        $this->result['status'] = 'success';
        $this->result['data']   = $result;

        return $this->result();
    }

    /**
     * Отметить отчет в ЦБ как "отправленный"
     *
     * @param MkoReport $mkoReport
     *
     * @return array
     */
    public function markMkoReportAsSent(MkoReport $mkoReport): array
    {
        try {
            $mkoReport->update(['is_sent' => !$mkoReport->is_sent]);
            $this->result['status'] = 'success';
        } catch (\Throwable $e) {
            Log::channel('mko_to_bank_errors')->error('MARK-REPORT-AS-SENT: ',
                                                      [$e->getCode(), $e->getMessage(), $e->getTrace()]);
            $this->result['status']           = 'error';
            $this->result['response']['code'] = $e->getCode();
            $this->message('danger', $e->getMessage());
        }

        return $this->result();
    }

    /**
     * Отметить отчет в ЦБ как "ошибочный"
     *
     * @param MkoReport $mkoReport
     *
     * @return array
     */
    public function markMkoReportAsError(MkoReport $mkoReport): array
    {
        try {
            $mkoReport->update(['is_error' => !$mkoReport->is_error]);
            $this->result['status'] = 'success';
        } catch (\Throwable $e) {
            Log::channel('mko_to_bank_errors')->error('MARK-REPORT-AS-ERROR: ',
                                                      [$e->getCode(), $e->getMessage(), $e->getTrace()]);
            $this->result['status']           = 'error';
            $this->result['response']['code'] = $e->getCode();
            $this->message('danger', $e->getMessage());
        }

        return $this->result();
    }
}
