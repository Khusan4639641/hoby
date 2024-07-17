<?php

namespace App\Http\Controllers\Core;

use App\Enums\CategoriesEnum;
use App\Helpers\CategoryHelper;
use App\Helpers\FileHelper;
use App\Helpers\QRCodeHelper;
use App\Helpers\SellerBonusesHelper;
use App\Helpers\SmsHelper;
use App\Helpers\V3\OTPAttemptsHelper;
use App\Http\Requests\Core\ContractController\DownloadActRequest;
use App\Http\Requests\SaveContractUrl;
use App\Models\Buyer;
use App\Models\BuyerPersonal;
use App\Models\CancelContract;
use App\Models\Contract;
use App\Models\Contract as Model;
use App\Models\ContractPaymentsSchedule;
use App\Models\ContractUrl;
use App\Models\File;
use App\Models\GeneralCompany;
use App\Services\FileHistoryService;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\SellerBonus;
use App\Models\KatmRegion;
use App\Models\Collector;
use App\Services\MFO\MFOPaymentService;
use App\Traits\UzTaxTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon as Illuminate_Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;
use Exception;

use App\Http\Requests\Collector\Contract\GetRequest;
use App\Http\Requests\Collector\KatmRegion\AttachRequest;


class ContractController extends CoreController
{

    private $validatorRules = [
        'user_id' => ['required', 'integer'],
        'partner_id' => ['required', 'integer'],
        'company_id' => ['required', 'integer'],
        'period' => ['required', 'integer'],
        'total' => ['required', 'numeric']
    ];

    private $customSignValidationMessages = [
        'id.required' => 'The :attribute field is required.',
        'id.integer' => 'The :attribute must be an integer.',
        'id.exists' => 'The selected :attribute is invalid.',
        'sign.required' => 'The :attribute field is required.',
        'sign.file' => 'The :attribute must be a file.',
    ];

    /**
     * ContractController constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = app(Model::class);
    }


    /**
     * @param array $params
     * @return array
     */
    public function filter($params = [])
    {
        $params['test'] = 0;

        // dev_nurlan 30.03.2022
        if (isset($params['act_status'])) {
            if (
                ($params['act_status'] === 1) || // SQL: WHERE `act_status` = 1
                (is_array($params['act_status']) && ($params['act_status'] === [0, 2])) // SQL: WHERE `act_status` IN (0, 2)
            ) {
                $params['status'] = [1, 3, 4]; // SQL: WHERE `status` IN (1, 3, 4)
            }
        }

        if (isset($params['sortingBy']) && $params['sortingBy'] == 'status') {
            return $this->filterByStatus($params);
        }
        return parent::filter($params);
    }

    public function filterByStatus($params = [])
    {
        $result = [];
        $query = $this->model::query();
        $contracts = $query->where('user_id', $params['user_id'])->whereHas('order')->get();
        $filters = [1, 3, 4, 9, 0, 2, 5];
        if (count($contracts) > 0)
            foreach ($filters as $filter) {
                foreach ($contracts as $contract)
                    if ($contract->status == $filter)
                        $result[] = $contract;
            }
        $total = $contracts->count();
        $result = array_slice($result, $params['offset'], $params['limit']);
        $result = collect($result);
        return [
            'result' => $result ?? [],
            'total' => $total
        ];
    }

    public function list(array $params = [])    // "routes/web.php": "/ru/panel/contracts/list"
    {
        $user = Auth::user();

        $resultSuccess = 'success';

        //Get data from REQUEST if api_token is set
        $request = request()->all();
        if (isset($request['api_token']))
            $params = $request;

        // Search passport number
        if (isset($params['passport_number'])) {
            $buyerPersonal = BuyerPersonal::where('passport_number_hash', md5($params['passport_number']))->first();
            if ($buyerPersonal) {
                $params['user_id'] = $buyerPersonal['user_id'];
            } else {
                $params['user_id'] = 0;
                $resultSuccess = 'error';
            }
        }

        if (isset($params['fio'])) {
            $params['user_fio'] = $params['fio'];
        }

        //Filter elements
        $filter = $this->filter($params);

        $active_statuses = [
            Contract::STATUS_ACTIVE,            // contract->status = 1 // Открыт, в рассрочке
            Contract::STATUS_OVERDUE_60_DAYS,   // contract->status = 3 // Просрочка 60 дней
            Contract::STATUS_OVERDUE_30_DAYS,   // contract->status = 4 // Просрочка 30 дней
            Contract::STATUS_COMPLETED,         // contract->status = 9 // Закрыт
        ];

        //Render items
        foreach ($filter['result'] as $index => $item) {
            $item->permissions = $this->permissions($item, $user);

            if (!$user->can('detail', $item)) {
                $filter['result']->forget($index);
            }

            //Debt calculation
            $item->totalDebt = 0;

            if (in_array((int)$item->status, $active_statuses, true)) {
                $item->totalDebt = $item->debt_sum;    // contract_payments_schedule + collect_cost + autopay_history
                if(( !empty($item->cancel_reason) && $item->status == Contract::STATUS_COMPLETED )){
                    $item->totalDebt = 0;
                }
            }

        }

        //Collect data
        $this->result['response']['total'] = $filter['total'];
        $this->result['status'] = $resultSuccess;

        //Format data
        if (isset($params['list_type']) && $params['list_type'] == 'data_tables')
//            $filter['result'] = (new \App\Http\Controllers\Web\Panel\ContractController)->formatDataTables($filter['result']);
            $filter['result'] = $this->formatDataTables($filter['result']);

        $this->result['data'] = $filter['result'];

        //Return data
        return $this->result();
    }


    /**
     * @param array $params
     * @param bool $private
     * @return array|false|string
     */
    public function add($params = [], $private = false)
    {
        if (count($params) == 0)
            $params = \Illuminate\Support\Facades\Request::all();

        $validator = $this->validator($params, $this->validatorRules);

        if ($validator->fails()) {
            $this->result['status'] = 'error';
            $this->result['response']['errors'] = $validator->errors();
        } else {
            $partner = Partner::find($params['partner_id']);

            $isAllowedToOnlineSignature = $partner->company ? $partner->company->is_allowed_online_signature : false;

            //Save contract
            $contract = new Model();
            $contract->partner_id = $partner->id;
            $contract->company_id = $partner->company_id;
            $contract->order_id = $params['order_id'];
            $contract->user_id = $params['user_id'];
            $contract->deposit = $params['deposit'];
            $contract->total = $params['total'];
            $contract->balance = $params['total'];
            $contract->period = $params['period'];
            $contract->status = $params['status'];
            $contract->offer_preview = $params['offer_preview'];
            $contract->confirmation_code = $params['confirmation_code'];
            $contract->price_plan_id = $params['price_plan_id'] ?? 0;
            $contract->ext_order_id = $params['ext_order_id'] ?? null;
            $contract->is_allowed_online_signature = $isAllowedToOnlineSignature;
            $contract->general_company_id = $partner->company->generalCompany->id;
            $contract->ox_system = $params['ox_system'] ?? 0; // все договоры от ox system


            if ($buyer = Buyer::where(['id' => $params['user_id'], 'status' => 4])->first()) {
                if ($buyer->doc_path == 1) {
                    $contract->doc_path = 1;  //  файлы и акты на новом сервере
                }
            }

            //TODO: Совместимость АПИ
            if ($params['confirmation_code'] == '')
                $contract->status = 0;

            if (isset($params['created_at'])) {
                $contract->created_at = $params['created_at'];
            }

            $contract->save();

            //$params['d_graf']; // день оплаты - цифра от 1 до 15 (1 числа всегда update)
            $now_day = Carbon::now()->format('d');
            $first_month_day = $now_day > 20 ? 15 : 1; // если договор оформлен >= 21, то первый месяц оплаты 15, остальные 1  - update 20.08.2021

            // Если это трехмесячная акция для обязательной предоплаты, пересчитываем график платежей
            // promotion - проценты и ндс уже заложены в цене
            if (isset($partner->company->promotion) && $partner->company->promotion == 1) {  // если это трехмесячная акция
                if ($params['period'] == 3) {

                    $prepayment = $partner->company->settings->promotion_percent / 100 * $contract->total;
                    $month_discount = $partner->company->settings['discount_' . $params['period']] / 100;

                    $total = $contract->total - $prepayment;
                    $total_deposit = 0;

                    // если был депозитный взнос, вычтем его из суммы долга партнеру
                    if ($contract->deposit > 0) {
                        $total_deposit = $contract->order->partner_total - $contract->deposit - $prepayment;
                    }

                    $correct_amount = $total_deposit; // сумма для корректировки

                    $params['payments'] = [];

                    // первый платеж
                    if ($contract->deposit > 0) {
                        // если был депозитный взнос, считаем без скидки
                        $params['payments'][] = [
                            'total' => $prepayment,
                            'origin' => $prepayment
                        ];
                    } else {
                        $params['payments'][] = [
                            'total' => $prepayment,
                            'origin' => $prepayment - $prepayment * $month_discount
                        ];
                    }


                    for ($i = 0; $i < $params['period']; $i++) {

                        // если был депозитный взнос, оставшиеся месяцы считаем без скидки
                        if ($contract->deposit > 0) {
                            $origin_month = round($total_deposit / $params['period'], 2);
                            $correct_amount -= $origin_month;

                            $params['payments'][] = [
                                'total' => round($total / $params['period'], 2),
                                //'origin' => round($total_deposit / $params['period'], 2)
                                'origin' => $origin_month
                            ];
                        } else {
                            $params['payments'][] = [   // сумма должна совпадать с суммой контракта - проверить!
                                'total' => round($total / $params['period'], 2),
                                'origin' => round(($total - $total * $month_discount) / $params['period'], 2)
                            ];
                        }
                    }

                    // корректируем последний платеж на недостающую сумму
                    if ($correct_amount > 0) {
                        $params['payments'][3]['origin'] += $correct_amount;
                    }
                }
            }

            //Save payments schedule
            for ($i = 0; $i < count($params['payments']); $i++) {
                $payment = new ContractPaymentsSchedule();
                $payment->contract_id = $contract->id;
                $payment->user_id = $contract->user_id;
                $payment->total = $params['payments'][$i]['total'];
                $payment->price = $params['payments'][$i]['origin'];
                $payment->balance = $params['payments'][$i]['total'];
                if ($i == 0) {
                    $day = $first_month_day;
                } else {
                    $day = $params['d_graf'];
                }

                if ($now_day > 27) {  // после 27 числа каждого месяца корректировка

                    if ($i == 0) {
                        $payment_date = Carbon::now()->addDay(25)->day($day)->format('Y-m-d H:i:s');
                    } else {
                        $payment_date = Carbon::now()->addDay(25)->addMonths($i)->day($day)->format('Y-m-d H:i:s');
                    }
                    $payment->payment_date = $payment_date;
                } else {
                    $payment_date = Carbon::now()->addMonths($i + 1)->day($day)->format('Y-m-d H:i:s');  // если договор оформлен >= 21, то первый месяц оплата 15, остальные 1
                    $payment->payment_date = $payment_date;
                }

                $payment->real_payment_date = $payment_date;   //не изменяется, когда реально ожидалась оплата при создании
                $payment->status = 0;

                // если это трехмесячная акция  - меняем даты платежей
                if (isset($partner->company->promotion) && $partner->company->promotion == 1) {
                    if ($params['period'] == 3) {
                        if ($i == 0) {  // если это первый месяц - предоплата
                            $now = Carbon::now()->format('Y-m-d H:i:s');
                            $payment->payment_date = $now;
                            $payment->real_payment_date = $now;
                        } else {
                            $payment->payment_date = Carbon::parse($payment->payment_date)->addMonths(-1)->format('Y-m-d H:i:s');
                            $payment->real_payment_date = Carbon::parse($payment->payment_date)->addMonths(-1)->format('Y-m-d H:i:s');
                        }

                    }
                }

                //$payment->payment_date      = strtotime(Carbon::now()->addMonths($i+1)->day($params['d_graf'])); //strtotime('+'.($i+1).' months');

                $payment->user_id = $contract->user_id;
                $payment->save();

            }

            //Create insure
            /*if($params['confirmation_code'] != '') {
                $insure = new InsureController;
                $request = new Request();
                $request->merge(['contract_id' => $contract->id]);
                $insure->add($request);
            }*/

            $this->result['status'] = 'success';
            $this->result['scheduleId'] = $payment->id;
            $this->result['data']['id'] = $contract->id;
            $this->result['data']['balance'] = $contract->balance;
            $this->message('success', __('billing/contract.txt_created'));
        }
        return $private ? $this->result : $this->result();
    }


    /**
     * @param int $id
     *
     * @return array|false|string
     */
    public function detail(int $id)
    {

        $user = Auth::user();

        $contract = $this->single($id);

        if ($contract) {
            if ($user->can('detail', $contract)) {
                $this->result['status'] = 'success';
                $this->result['data'] = $contract;
            } else {
                //Error: access denied
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 403;
                $this->message('danger', __('app.err_access_denied'));
            }
        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.err_not_found'));
        }

        return $this->result();
    }

    /**
     * @param array $params
     * @return array|false|string
     */
    public function changeActStatus($params = [])
    {
        $user = Auth::user();

        if (count($params) == 0)
            $params = \Illuminate\Support\Facades\Request::all();

        $contract = Model::where('id', $params['id'])->with('act')->first();

        if ($contract) {
            $contract->act_status = $params['act_status'];
            $contract->save();
            FileHistoryService::changeStatus($contract->id, $params['act_status'], 'act');
            $this->activateBonusesIfStatus($contract->order->products, $contract);

            $this->result['status'] = 'success';
            $this->message('success', __('panel/contract.txt_act_status_changed'));
        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.err_not_found'));
        }

        //$this->message( 'danger', __( 'app.err_access_denied' ) );

        return $this->result();
    }


    public function uploadAct($params = [])
    {
        $user = Auth::user();

        if (count($params) == 0)
            $params = \Illuminate\Support\Facades\Request::all();

        if (isset($params['act']))
            $fileArray = array('act' => $params['act']);

        $rules = array(
            'act' => 'mimes:jpg,png,pdf'
        );

        $validator = Validator::make($fileArray, $rules);

        if ($validator->fails()) {

            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('billing/order.upload_file_with_jpg_png_or_pdf_format'));

        } else {


            $contract = Model::where('id', $params['id'])->with('act')->first();


            if ($contract) {
                if ($params['act'] != null) {

                    //Delete files
                    $filesToDelete = $contract->act ? [$contract->act->id] : [];

                    //Upload files
                    $fileParams = [
                        'files' => ['act' => $params['act']],
                        'element_id' => $contract->id,
                        'model' => 'contract'
                    ];

                    Log::info('save contract act');
                    Log::info($fileParams);
                    FileHelper::upload($fileParams, $filesToDelete, true);

                    //Change contract act status
                    $contract->act_status = $params['act_status'] ?? 1;
                    $contract->save();
                    FileHistoryService::changeStatus($contract->id, $contract->act_status, 'act');
                    $contract->load('act');

                    //Prepare result
                    $this->result['data']['path'] = $contract->act->path;
                    $this->result['status'] = 'success';
                    $this->message('success', __('billing/contract.txt_act_uploaded'));
                } else {
                    $this->result['status'] = 'error';
                    $this->message('danger', __('app.err_upload'));
                }
            } else {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->message('danger', __('app.err_not_found'));
            }
        }

        //$this->message( 'danger', __( 'app.err_access_denied' ) );

        return $this->result();
    }

    /**
     * @param array $params
     * @return array|false|string
     */
    public function changeImeiStatus($params = [])
    {
        $user = Auth::user();

        if (count($params) == 0)
            $params = \Illuminate\Support\Facades\Request::all();

        $contract = Model::where('id', $params['id'])->with('imei')->first();

        if ($contract) {
            $contract->imei_status = $params['imei_status'];
            $contract->save();
            FileHistoryService::changeStatus($contract->id, $params['imei_status'], 'imei');
            $this->activateBonusesIfStatus($contract->order->products, $contract);

            $this->result['status'] = 'success';
            $this->message('success', __('panel/contract.txt_imei_status_changed'));
        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.err_not_found'));
        }

        //$this->message( 'danger', __( 'app.err_access_denied' ) );

        return $this->result();
    }


    public function uploadImei($params = [])
    {
        $user = Auth::user();

        if (count($params) == 0)
            $params = \Illuminate\Support\Facades\Request::all();

        if (isset($params['imei']))
            $fileArray = array('imei' => $params['imei']);

        $rules = array(
            'imei' => 'mimes:jpg,png,pdf'
        );

        $validator = Validator::make($fileArray, $rules);

        if ($validator->fails()) {

            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('billing/order.upload_file_with_jpg_png_or_pdf_format'));

        } else {

            $contract = Model::where('id', $params['id'])->with('imei')->first();

            if ($contract) {
                if ($params['imei'] != null) {

                    //Delete files
                    $filesToDelete = $contract->imei ? [$contract->imei->id] : [];

                    //Upload files
                    $fileParams = [
                        'files' => ['imei' => $params['imei']],
                        'element_id' => $contract->id,
                        'model' => 'contract'
                    ];
                    FileHelper::upload($fileParams, $filesToDelete, true);

                    //Change contract imai status
                    $contract->imei_status = $params['imei_status'] ?? 3;
                    $contract->save();

                    $contract->load('imei');

                    //Prepare result
                    $this->result['data']['path'] = $contract->imei->path;
                    $this->result['status'] = 'success';
                    $this->message('success', __('billing/contract.txt_imei_uploaded'));

                } else {
                    $this->result['status'] = 'error';
                    $this->message('danger', __('app.err_upload'));
                }
            } else {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->message('danger', __('app.err_not_found'));
            }
        }
        //$this->message( 'danger', __( 'app.err_access_denied' ) );

        return $this->result();
    }

    /**
     * @param array $params
     * @return array|false|string
     */
    public function changeContractStatus($params = [])
    {
        $user = Auth::user();

        if (count($params) == 0)
            $params = \Illuminate\Support\Facades\Request::all();

        $contract = Model::where('id', $params['id'])->first();

        if ($contract) {
            $contract->status = $params['contract_status'];

            if ($params['contract_status'] == 5) {
                $contract->cancel_reason = $params['cancel_reason'];
                $contract->canceled_at = date('Y-m-d H:i:s');
                $this->message('success', __('panel/buyer.txt_cancel_status_changed'));

            } else if ($params['contract_status'] == 1) {
                $contract->confirmed_at = date('Y-m-d H:i:s');
                $this->message('success', __('panel/buyer.txt_confirm_status_changed'));

            } else {
                $contract->cancel_reason = $params['cancel_reason'];
                $this->message('success', __('panel/contract.txt_status_changed'));
            }

            $contract->save();
            $this->result['status'] = 'success';

        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.err_not_found'));
        }

        //$this->message( 'danger', __( 'app.err_access_denied' ) );

        return $this->result();
    }

    /**
     * @param array $params
     * @return array|false|string
     */
    public function changeClientPhotoStatus($params = [])
    {
        $user = Auth::user();

        if (count($params) == 0)
            $params = \Illuminate\Support\Facades\Request::all();

        $contract = Model::where('id', $params['id'])->with('clientPhoto')->first();

        if ($contract) {
            $contract->client_status = $params['client_status'];
            $contract->save();
            FileHistoryService::changeStatus($contract->id, $params['client_status'], 'client_photo');
            $this->activateBonusesIfStatus($contract->order->products, $contract);

            $this->result['status'] = 'success';
            $this->message('success', __('panel/contract.txt_status_changed'));
        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.err_not_found'));
        }

        //$this->message( 'danger', __( 'app.err_access_denied' ) );

        return $this->result();
    }

    public function uploadClientPhoto($params = [])
    {
        $user = Auth::user();

        if (count($params) == 0)
            $params = \Illuminate\Support\Facades\Request::all();

        if (isset($params['client_photo']))
            $fileArray = array('client_photo' => $params['client_photo']);

        $rules = array(
            'client_photo' => 'mimes:jpg,png,pdf'
        );

        $validator = Validator::make($fileArray, $rules);

        if ($validator->fails()) {

            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('billing/order.upload_file_with_jpg_png_or_pdf_format'));

        } else {

            $contract = Model::where('id', $params['id'])->with('clientPhoto')->first();


            if ($contract) {
                if ($params['client_photo'] != null) {

                    //Delete files
                    $filesToDelete = $contract->client_photo ? [$contract->client_photo->id] : [];

                    //Upload files
                    $fileParams = [
                        'files' => ['client_photo' => $params['client_photo']],
                        'element_id' => $contract->id,
                        'model' => 'contract'
                    ];
                    FileHelper::upload($fileParams, $filesToDelete, true);

                    //Change contract client photo status
                    $contract->client_status = isset($params['client_status']) ? $params['client_status'] : 3;  //
                    $contract->save();

                    $contract->load('clientPhoto');

                    //Prepare result
                    $this->result['data']['path'] = $contract->clientPhoto->path;
                    $this->result['status'] = 'success';
                    $this->message('success', __('billing/contract.txt_imei_uploaded'));
                } else {
                    $this->result['status'] = 'error';
                    $this->message('danger', __('app.err_upload'));
                }
            } else {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;
                $this->message('danger', __('app.err_not_found'));
            }
        }
        //$this->message( 'danger', __( 'app.err_access_denied' ) );

        return $this->result();
    }


    public function uploadCancelAct($params = [])
    {
        $user = Auth::user();

        if (count($params) == 0)
            $params = \Illuminate\Support\Facades\Request::all();

        $contract = Model::where('id', $params['id'])->with('cancelAct')->first();

        if ($contract) {
            if ($params['cancel_act'] != null) {

                //Delete files
                $filesToDelete = $contract->cancelAct ? [$contract->cancelAct->id] : [];

                //Upload files
                $fileParams = [
                    'files' => ['cancel_act' => $params['cancel_act']],
                    'element_id' => $contract->id,
                    'model' => 'contract'
                ];
                FileHelper::upload($fileParams, $filesToDelete, true);

                //Change contract act status
                $contract->cancel_act_status = $params['cancel_act_status'] ?? 1;
                $contract->save();

                $contract->load('cancelAct');

                //Prepare result
                $this->result['data']['path'] = $contract->cancelAct->path;
                $this->result['status'] = 'success';
                $this->message('success', __('billing/contract.txt_cancel_act_uploaded'));
            } else {
                $this->result['status'] = 'error';
                $this->message('danger', __('app.err_upload'));
            }
        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.err_not_found'));
        }

        //$this->message( 'danger', __( 'app.err_access_denied' ) );

        return $this->result();
    }

    /**
     * @param array $params
     * @return array|false|string
     */
    public function changeCancelActStatus($params = [])
    {
        $user = Auth::user();

        if (count($params) == 0)
            $params = \Illuminate\Support\Facades\Request::all();

        $contract = Model::where('id', $params['id'])->with('cancelAct')->first();

        if ($contract) {
            $contract->cancel_act_status = $params['cancel_act_status'];

            if ($contract->cancel_act_status == 3) {  // если акт принят, отменяем договор
                $contract->status = 5;
                $contract->canceled_at = date('Y-m-d H:i:s');
                $contract->order->status = 5;
                if (isset($contract->buyer->settings)) { // вернуть лимит
                    $settings = $contract->buyer->settings;
                    $settings->balance += ($contract->order->credit - $contract->deposit);
                    $settings->personal_account += $contract->deposit;
                    $settings->save();
                }
                $contract->save();
                $contract->order->save();

            } else if ($contract->cancel_act_status == 2) {  // если акт не принят, указываем причину отказа
                if (isset($params['cancel_reason'])) {
                    $contract->cancel_reason = $params['cancel_reason'];
                }
                $contract->save();
            }
            FileHistoryService::changeStatus($contract->id, $params['cancel_act_status'], 'act');
            $this->result['status'] = 'success';
            $this->message('success', __('panel/contract.txt_cancel_act_status_changed'));
        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.err_not_found'));
        }

        //$this->message( 'danger', __( 'app.err_access_denied' ) );

        return $this->result();
    }

    public function CancelContract(Request $request)
    {

        $result = [
            'result' => [
                'status' => 0
            ]
        ];

        if (!$request->has('contract_id')) {
            $result['result']['status'] = 0;
            $result['error'] = 'empty contract_id';
            return $result;
        }


        $contract = Model::where(['id' => $request->contract_id, 'status' => 1])->with('order', 'buyer')->first();

        if ($contract) {

            //Create SMS code
            $code = OTPAttemptsHelper::generateCode(6);

            //$link = 'https://test.uz/contract/' . $contract->id;

            // $msg = $code . ' - shartnomani bekor qilish kodi. ' .  $link;

            $contract_date = date('Y.m.d', strtotime($contract->created_at));

            $msg = 'Kod: ' . $code . '. ' . $contract_date
                . ' da rasmiylashtirilgan ' . $contract->id
                . ' shartnomani bekor qilish kodi. Tel: ' . callCenterNumber(2);

            $hashedCode = Hash::make($contract->buyer->phone . $code);

            [$res, $http_code] = SmsHelper::sendSms($contract->buyer->phone, $msg);
            Log::info($res);
            Redis::set($contract->buyer->phone . '-' . $contract->id, $hashedCode);

            if (($http_code === 200) || ($res === SmsHelper::SMS_SEND_SUCCESS)) {

                Log::channel('contracts')->info('Отправка смс кода клиенту ' . $contract->buyer->phone . ' об отмене контракта ' . $contract->id . ' Партнер ' . $contract->partner_id);
                Log::channel('contracts')->info($contract->buyer->phone . ': ' . $msg);

                $result['result']['status'] = 1;
                $result['message'] = 'sms sended';

            } else {
                Log::channel('contracts')->info("НЕ отправлен смс клиенту об отмене контракта");

                $result['result']['status'] = 0;
                $result['message'] = 'sms not sended';
            }
        } else {
            $result['result']['status'] = 0;
            $result['message'] = 'contract not found';
        }

        return $result;

    }

    public function CheckCancelSms(Request $request)
    {

        $bErr = false;
        $result = [
            'result' => [
                'status' => 0
            ],
            'error' => null
        ];

        if (!$request->has('contract_id')) {
            $result['error'] = 'contract_id empty';
            $result['result']['status'] = 0;
            $bErr = true;
        } elseif (!$request->has('code')) {
            $result['error'] = 'sms code empty';
            $result['result']['status'] = 0;
            $bErr = true;
        }

        $contract = Model::where(['id' => $request->contract_id, 'status' => 1])->with('order', 'buyer')->first();

        if ($contract) {
            if (!$bErr) {
                $hash = Redis::get($contract->buyer->phone . '-' . $request->contract_id);

                $checkSms = new Request();
                $checkSms->merge([
                    'code' => $request->code,
                    'hashedCode' => $hash,
                    'phone' => $contract->buyer->phone
                ]);

                $resultCheck = $this->checkSmsCode($checkSms);

                if ($resultCheck['status'] == 'success') {
                    $contract->cancel_reason = $request->code;
                    $contract->canceled_at = date('Y-m-d H:i:s');
                    $contract->status = 5;
                    $contract->cancellation_status = 3; // Отмена подтверждена
                    $contract->order->status = 5;

                    $limit = $contract->order->credit - $contract->deposit;
                    if (isset($contract->price_plan) && $contract->price_plan->is_mini_loan) {
                        //мини лимит
                        $contract->buyer->settings->mini_balance += $limit;  // вернуть лимит
                    } else {
                        $contract->buyer->settings->balance += $limit;  // вернуть лимит
                    }
                    if ($contract->deposit > 0) $contract->buyer->settings->personal_account += $contract->deposit; // вернуть депозит на ЛС, если он был

                    //TODO: продумать идентификатор контрактов по 3мес акции, к моменту отмены договора акция уже могла быть уже выключена
                    //если была предварительная оплата по акции на 3 мес, вернуть деньги на ЛС
                    if ($contract->schedule[0]->status == 1) {  // если месяц оплачен, проверим, была ли акция

                        //если дата первого оплаченного месяца совпадает с датой подтверждения контракта (confirmed_at), значит была акция
                        $confirmed_at = Carbon::parse($contract->confirmed_at)->format('dm');
                        $paid_at = Carbon::parse($contract->schedule[0]->paid_at)->format('dm');

                        if ($confirmed_at == $paid_at) {

                            $contract->buyer->settings->personal_account += $contract->schedule[0]->total; // вернуть деньги на ЛС

                            // записать отмену транзакции в payments
                            $pay = new Payment;
                            $pay->schedule_id = $contract->schedule[0]->id;
                            $pay->type = 'refund'; // отмена
                            $pay->order_id = $contract->order_id;
                            $pay->contract_id = $contract->id;
                            $pay->amount = -1 * $contract->schedule[0]->total;
                            $pay->user_id = $contract->buyer->id;
                            $pay->payment_system = 'ACCOUNT';
                            $pay->status = 1;
                            $pay->save();
                        }
                    }

                    $contract->save();
                    $contract->order->save();
                    $contract->buyer->settings->save();

                    //MFO Отмена договора
                    if ($contract->general_company_id === GeneralCompany::MFO_COMPANY_ID) {
                        $service = new MFOPaymentService();
                        $service->cancelTransactionCheckSms($contract);
                    }

                    // создать минусовой договор с датой создания = дата отмены
                    $cancel_contract = new CancelContract();
                    $cancel_contract->contract_id = $contract->id;
                    $cancel_contract->user_id = $contract->user_id;
                    $cancel_contract->created_at = $contract->canceled_at;  // датой создания = дата отмены
                    $cancel_contract->canceled_at = $contract->canceled_at;  // датой создания = дата отмены
                    $cancel_contract->total = -1 * $contract->total;
                    $cancel_contract->balance = -1 * $contract->balance;
                    $cancel_contract->deposit = -1 * $contract->deposit;
                    $cancel_contract->save();

                    SellerBonusesHelper::refundByContract($contract->id);

                    UzTaxTrait::refundReturnProduct($contract->id);


                    Redis::del($contract->buyer->phone . '-' . $contract->id);
                    Redis::del($contract->buyer->phone); // ??

                    $result['result']['status'] = 1;
                    $result['result']['contract_id'] = $contract->id;
                    $result['result']['message'] = 'contract has been canceled';
                } else {
                    $result['error'] = 'sms code not correct';
                }
            }
        } else {
            $result['result']['status'] = 0;
            $result['error'] = 'contract not found';
        }

        return $result;
    }

    /**
     * загрузка акта через апи  - для интернет магазинов
     * @param array $params
     * @return array|false|string
     */
    public function uploadActApi($params = [])
    {
        $user = Auth::user();

        if (count($params) == 0)
            $params = \Illuminate\Support\Facades\Request::all();

        $contract = Model::where('order_id', $params['id'])->with('act')->first();

        if ($contract) {
            if (isset($params['act']) && $params['act'] != null) {

                //Delete files
                $filesToDelete = $contract->act ? [$contract->act->id] : [];

                //Upload files
                $fileParams = [
                    'files' => ['act' => $params['act']],
                    'element_id' => $contract->id,
                    'model' => 'contract'
                ];

                Log::info('save contract act');
                Log::info($fileParams);
                FileHelper::upload($fileParams, $filesToDelete, true);

                //Change contract act status
                $contract->act_status = $params['act_status'] ?? 1;
                FileHistoryService::changeStatus($contract->id, $contract->act_status, 'act');
                $contract->save();
                $contract->load('act');

                //Prepare result
                $this->result['status'] = 'success';
                // $this->message( 'success', __( 'billing/contract.txt_act_uploaded' ) );
                $this->message('success', 'act_uploaded');
            } else {
                $this->result['status'] = 'error';
                $this->message('danger', 'err_upload');
            }
        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', 'contract_not_found');
        }

        // $this->message( 'danger', __( 'app.err_access_denied' ) );

        return $this->result();
    }

    // сместить график на первое число
    private function changeSchedulePaymentDate(&$schedules)
    {
        if ($schedules) {

            foreach ($schedules as $schedule) {
                if ($schedule->status == 1) continue;
                $y = date('Y', strtotime($schedule->payment_date));
                $m = date('m', strtotime($schedule->payment_date));
                $d = date('d', strtotime($schedule->payment_date));
                if ((int)$d == 1) continue;
                $schedule->payment_date = strtotime(date($y . '-' . $m . '-01 00:00:01'));
                $schedule->save();
                Log::channel('cron_contract_date')->info('SET schedule payment_date ' . date($y . '-' . $m . '-01 00:00:01'));

            }
        }

    }

    public function signContract(Request $request)
    {

        $clientID = Auth::id();

        $validator = $this->validator($request->all(), [
            'id' => ['required', 'integer', 'exists:contracts'],
            'sign' => ['required', 'file'],
        ],
            [],
            $this->customSignValidationMessages);

        $contractID = $request->id;
        $signFile = $request->file('sign');

        $contract = Contract::find($contractID);

        if ($validator->fails()) {
            $this->result['status'] = 'error';
            $this->result['response']['message'] = [
                'type' => 'validation',
                'text' => $validator->errors()->first(),
            ];
            $this->error($validator->errors());
            return $this->result();
        }

        if ($contract->user_id != $clientID) {
            $message = 'The contract does not belong to the client';
            $this->result['status'] = 'error';
            $this->result['response']['message'] = [
                'type' => 'validation',
                'text' => $message,
            ];
            $this->error(['id' => $message]);
            return $this->result();
        }

        if (!$contract->is_allowed_online_signature) {
            $message = 'The contract does not have permission for online signature';
            $this->result['status'] = 'error';
            $this->result['response']['message'] = [
                'type' => 'validation',
                'text' => $message,
            ];
            $this->error(['id' => $message]);
            return $this->result();
        }


        try {

            $signParams = [
                'files' => [File::TYPE_SIGNATURE => $signFile],
                'element_id' => $contract->id,
                'model' => 'contract'
            ];

            FileHelper::upload($signParams, [], true);

            $path = 'contract/' . $contract->id . '/';

            $langCode = $request->language_code ?: '';

            FileHelper::generateAndUploadHtml($contract->id, 'contract', File::TYPE_SIGNED_CONTRACT, $langCode, $path, 'billing.order.parts.account_pdf', ['order' => $contract->order, 'signPath' => FileHelper::url($contract->signature->path)]);

        } catch (Exception $e) {
            $this->result['status'] = 'error';
            $this->error($e->getMessage());
            return $this->result();
        }

        $this->result['status'] = 'success';
        $this->result['data'] = [
            'link' => FileHelper::url($contract->signedContract->path),
        ];

        return $this->result();

    }

    public function saveUrls(SaveContractUrl $request)
    {
        if (                                            // Если тело запроса пустое
            is_null($request->validated()) ||
            !is_array($request->validated()) ||
            !isset     ($request->validated()["data"])
        ) {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 400;    // Bad Request.
            $this->message('danger', __('reports.contract_urls_empty_request'));
            unset($this->result['response']['errors'], $this->result['data']);
            return $this->result();
        }

        $contract_urls_array = $request->validated()["data"];
        foreach ($contract_urls_array as $contract_url) {
            if (empty($contract_url["url"] ?? 0)) {
                continue;
            }
            try {
                if (Contract::findOrFail($contract_url["contract_id"])) {
                    $contract_created_at = Illuminate_Carbon::parse((int)$contract_url["contract_created_at"])
                        ->format('Y-m-d H:i:s');
                    ContractUrl::updateOrCreate(
                        ['contract_id' => $contract_url["contract_id"]],               // If there's a $contract_url with given $request->contract_id,
                        [                                                              // set/update the url, contract_summa and contract_created_at.
                            'url' => $contract_url["url"],             // If no matching model exists, create one.
                            'contract_summa' => $contract_url["contract_summa"],
                            'contract_created_at' => $contract_created_at
                        ]
                    );
//                    $this->result['data']['contract_urls'][] = $contract_url["contract_id"];
                }
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
                $this->result['status'] = 'error';
                $this->result['response']['code'] = 404;    // Bad Request.
                $this->message('danger', $contract_url["contract_id"] . " " . __('reports.contract_urls_contract_not_found'));
                unset($this->result['response']['errors'], $this->result['data']);
                return $this->result();
            }
        }
        $this->result['status'] = 'success';
        $this->message('success', __('reports.contract_urls_successfully_saved'));
        unset($this->result['response']['errors'], $this->result['data']);

        return $this->result();
    }

    /**
     * Активация бонусов в зависимости от статусов контракта - act_status=3,  imei_status = 1, client_status=1
     * Проверяет товары на категорию 1 (моб.телефоны), для проверки IMEI
     * @param $products
     * @param Contract $contract
     */
    private function activateBonusesIfStatus($products, Contract $contract): bool
    {
        if ($contract->client_status !== 1) {
            return false;
        }
        $sellerID = SellerBonus::where('contract_id', $contract->id)->first('seller_id');
        foreach ($products as $product) {
            if (CategoryHelper::isPhone($product['category_id'])) {
                if ($contract->imei_status !== 1) {
                    return false;
                }
                Log::channel('contracts')->info('Активация бонусов по контракту - ' . $contract->id . ', продавец - ' . $sellerID);
                return SellerBonusesHelper::activateBonusesByContract($contract->id);
            }
        }
        Log::channel('contracts')->info('Активация бонусов по контракту - ' . $contract->id . ', продавец - ' . $sellerID);
        return SellerBonusesHelper::activateBonusesByContract($contract->id);
    }

    public function recoveryContracts(GetRequest $request)
    {
        $region = $request->region;
        $local_region = $request->local_region;
        $page = $request->page ? $request->page : 1;

        $contractsQuery = Contract::where('expired_days', '>=', 90);

        if ($region) {
            $contractsQuery->whereHas('buyer', function (Builder $query) use ($region) {
                $query->where('region', $region);
            });
        }
        if ($local_region) {
            $contractsQuery->whereHas('buyer', function (Builder $query) use ($local_region) {
                $query->where('local_region', $local_region);
            });
        }

        $perPage = 15;
        $page = $request->page ? $request->page : 1;
        $pageOffset = ($page - 1) * $perPage;
        $last_page = ceil($contractsQuery->count() / $perPage);

        return [
            'last_page' => $last_page,
            'contracts' => $contractsQuery
                ->with(['company', 'buyer'])
                ->orderBy('expired_days', 'desc')
                ->latest()
                ->offset($pageOffset)->limit($perPage)
                ->get()
                ->append('has_collector')
                ->append('delay_sum')
                ->append('payment_sum')
                ->append('status_caption')
        ];
    }

    public function setKatmRegion(AttachRequest $request, Contract $contract)
    {
        $katm_region = KatmRegion::find($request->katm_region_id);

        $buyer = $contract->buyer;

        if ($buyer === null) {
            return response([
                // TODO: error.key
                'error' => 'Buyer not found'
            ], 500);
        }

        if ($buyer->local_region !== null) {
            // TODO: method for detaching contract from current collector
            return response([
                // TODO: error.key
                'error' => 'Contract attached'
            ], 500);
        }

        $contract->buyer()->update([
            'region' => $katm_region->region,
            'local_region' => $katm_region->local_region
        ]);

        $collector = Collector::whereHas('katm_regions', function (Builder $query) use ($katm_region) {
            $query->where('local_region', $katm_region->local_region);
        })->first();

        if ($collector) {
            $collector->contracts()->attach($contract->id);
        }

        return response('OK');
    }

    public function showFiles(Request $request)
    {
        return FileHistoryService::show($request->id);
    }

    public function DownloadAct(DownloadActRequest $request)
    {

        $contract_id = $request->validated()["contract_id"];

        if (
            $link = File::where([
                ['element_id', $request->contract_id],
                ['type', 'contract_pdf']
            ])->first()
        ) {
            $act_file_path = $link->path;
        } else {
            $act_file_path = "contract/" . $request->contract_id . "/buyer_account_" . $contract_id . ".pdf";
        }

        if (!FileHelper::exists($act_file_path)) {
            abort(404);
        }

        $file_name = "act_{$contract_id}.pdf";

        return Storage::disk('sftp')->download($act_file_path, $file_name);
    }

    public function GenerateAct(Request $request, Contract $contract, $type = 'contract_pdf')
    {
        $user = Auth::user();
        $buyer = $contract->buyer;

        $file = File::where([
            ['element_id', $contract->id],
            ['type', $type]
        ])
            ->orderBy("created_at", "desc")
            ->first();


        // Какой-то дефолтный (не уникальный) путь, видимо для обратной совместимости с чем-то...
        if ($type == 'contract_pdf_qr') {
            $act_file_path = "contract/" . $contract->id . "/buyer_account_qr" . $contract->id . ".pdf";
        } elseif ($type == 'contract_pdf') {
            $act_file_path = "contract/" . $contract->id . "/buyer_account_" . $contract->id . ".pdf";
        }

        if ($file) {
            $act_file_path = $file->path;
        }

        if (FileHelper::exists($act_file_path)) {

            $file_name = "act_{$contract->id}.pdf";

            return Storage::disk('sftp')->download($act_file_path, $file_name);
        }


        // Если акта нету, то генерируем новый и отдаём на скачивание

        $result = (new OrderController)->detail($contract->order->id);
        $result['data']['status_list'] = Config::get('test.order_status');

        $folderContact = 'contract/';
        $folder = $folderContact . $contract->id;

        # Offer .PDF
        $uniqueID = md5(time());

        $namePdf = "vendor_offer_{$contract->id}.pdf";


        ob_start();
        QRCodeHelper::url(FileHelper::sourcePath() . $folder . '/' . $namePdf);
        $imagedata = ob_get_clean();

        Log::info(
            "QR-code containing a PDF-act path was created, " .
            "something like this: " . FileHelper::sourcePath() . $folder . '/' . $namePdf
        );

        $result['data']['qrcode'] = '<img src="data:image/png;base64,' . base64_encode($imagedata) . '"/>';

        // Если в таблице Files есть запись, то берём название папки из этой записи
        if ($file) {
            $fileInfo = pathinfo($file->path);
            $act_file_path = $fileInfo["dirname"] . "/" . $namePdf;   //  'contract/123456/ {$file->path <== старый md5(time())} /vendor_offer_123456.pdf';
        } // А иначе генерируем новую папку с уникальным названием
        else {
            $act_file_path = "$folder/$uniqueID/$namePdf";   //  'contract/123456/ {md5(time())} /vendor_offer_123456.pdf';
        }

        // Если по такому пути нет файла(акта) с таким именем, то генерируем новый файл(акт)
        if (!FileHelper::exists($act_file_path)) {
            FileHelper::generateAndUploadPDF($act_file_path, 'billing.order.parts.offer_pdf', $result['data']);
            Log::info("ContractID: $contract->id. vendor_pdf create " . $act_file_path);
        }
        Log::info("ContractID: $contract->id. Выше должна была создаться запись 'vendor_pdf create'.\n" .
            "Если отсутствует, то PDF-файл не был создан, так как файл с таким именем и расположением уже имелся."
        );

        $result['data']['offer_pdf'] = '/storage/contract/' . $contract->id . '/' . $uniqueID . '/' . $namePdf;
        $result['data']['act_type'] = $type;

        # Account .PDF
        if ($type == 'contract_pdf_qr') {
            $namePdf = 'buyer_account_qr_' . $contract->id . '.pdf';
        } elseif ($type == 'contract_pdf') {
            $namePdf = 'buyer_account_' . $contract->id . '.pdf';
        }

        // Если в таблице Files есть запись, то берём название папки из этой записи
        if ($file) {
            $fileInfo = pathinfo($file->path);
            $act_file_path = $fileInfo["dirname"] . "/" . $namePdf;   //  'contract/123456/ {$file->path <== старый md5(time())} /buyer_account_123456.pdf';
        } // А иначе генерируем новую папку с уникальным названием
        else {
            $act_file_path = "$folder/$uniqueID/$namePdf";   //  'contract/123456/ {md5(time())} /buyer_account_123456.pdf';
        }

        // Если по такому пути нет файла(акта) с таким именем, то генерируем новый файл(акт)
        // Если по такому пути есть файла(акта) с таким именем, то генерируем новое уникальное название для папки файла(акта)
        if (FileHelper::exists($act_file_path)) {
            $uniqueID = md5(time());
            $act_file_path = "$folder/$uniqueID/$namePdf";   //  'contract/123456/ {md5(time())} /buyer_account_123456.pdf';
        }

        $fileInfo = pathinfo($act_file_path);

        $file = new File;
        $file->element_id = $contract->id;
        $file->model = 'contract';
        $file->type = $type;
        $file->name = $fileInfo['basename'];
        $file->path = $act_file_path;
        $file->language_code = session('locale');
        $file->user_id = $user->id;
        $file->doc_path = 1;
        $file->save();

        FileHelper::generateAndUploadPDF($act_file_path, 'billing.order.parts.account_pdf', $result['data']);
        Log::info("ContractID: $contract->id. buyer_account_pdf create " . $act_file_path);

        $buyerInfo = Buyer::getInfo($buyer->id); //  buyer_id
        Log::channel('contracts')->info('buyerInfo');
        Log::channel('contracts')->info($buyerInfo);


        if (!FileHelper::exists($act_file_path)) {
            Log::info("ContractID: $contract->id. THERE'S NO ACT-FILE in the given route: \"" . $act_file_path .
                "\", it seems like act wasn't created!");
            abort(500);
        }

        $file_name = "act_{$contract->id}.pdf";

        return Storage::disk('sftp')->download($act_file_path, $file_name);
    }
}
