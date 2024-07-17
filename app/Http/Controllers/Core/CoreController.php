<?php

namespace App\Http\Controllers\Core;

use App\Facades\BuyerDebtor;
use App\Helpers\EncryptHelper;
use App\Helpers\LocaleHelper;
use App\Http\Controllers\Controller;
use App\Models\Buyer;
use App\Models\CollectCost;
use App\Models\Contract;
use App\Models\CronUsersDelays;
use App\Models\Order;
use App\Models\Partner;
use App\Models\User;
use Carbon\Carbon;
use Doctrine\DBAL\Driver\AbstractDB2Driver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use App\Traits\SmsTrait;
use Illuminate\Support\Str;
use Svg\Tag\Stop;

class CoreController extends Controller
{
    /**
     * @OA\Info(
     *      version="1.0.0",
     *      description="",
     *      @OA\Contact(
     *          email="info@test.xyz"
     *      ),
     * )
     *
     * @OA\Server(
     *      url=L5_SWAGGER_CONST_HOST,
     * )
     *
     * @OA\Tag(
     *     name="Authorization",
     *     description="Authorization buyer, partner and employeer. For buyer first use method send-sms-code after receive SMS use method auth. For employeer use phone and password in method auth. For partner use partner_id and password"
     * )
     */

    use SmsTrait;

    public $model;

    public $loadWith = [];

    protected $defaultLocale;

    protected $result = [
        'status' => '',
        'response' => [
            'code' => '',
            'message' => [],
            'errors' => []
        ],
        'data' => [],
    ];

    /**
     * CoreController constructor.
     */
    public function __construct()
    {
        $languages = LocaleHelper::languages();
        foreach ($languages as $language)
            if ($language->default) $this->defaultLocale = $language->code;

    }

    /**
     * @param array $data
     * @param $baseRules
     * @param array $addRules
     * @param array $messages
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data, $baseRules, $addRules = [], $messages = [])
    {
        $rules = (!empty($addRules)) ? array_merge($baseRules, $addRules) : $baseRules;
        return Validator::make($data, $rules, $messages);
    }


    /**
     * @param $id
     * @param array $with
     *
     * @return Builder|Model|object
     */
    protected function single($id, $with = [])
    {
        $user = Auth::user();

        $single = $this->model->whereId($id)->with($with)->first();

        if ($user && $single) // 29,04,2021 - $single->permissions - добавил
            $single->permissions = $this->permissions($single, $user);

        return $single;
    }


    /**
     * @param array | Collection $data
     *
     * @return array
     */
    protected function formatDataTables($data)
    {
        return [
            'draw' => 1,
            'recordsTotal' => $this->result['response']['total'],
            'recordsFiltered' => count($data),
            'data' => $data,
            'error' => ''
        ];
    }


    /**
     * @param $item
     * @param User $user
     *
     * @return array
     */
    protected function permissions($item, User $user)
    {
        $permissions = [];

        if ($user->can('detail', $item)) {
            $permissions[] = 'detail';
        }
        if ($user->can('modify', $item)) {
            $permissions[] = 'modify';
        }
        if ($user->can('delete', $item)) {
            $permissions[] = 'delete';
        }

        return $permissions;
    }


    /**
     * Get items ids list
     *
     * @param array $params
     * @return array|bool|false|string
     */
    public function list(array $params = [])
    {

        $user = Auth::user();

        //Get data from REQUEST if api_token is set
        $request = Request::all();

        if (isset($request['api_token']) && sizeof($params) == 0) {
            $params = $request;
        }
        unset($params['api_token']);

        //Filter elements
        $filter = $this->filter($params);

        $debts = 0;
        // при оформлении договора показать (сумму?), что он в просрочке и не давать оформить
        if (isset($params['phone__like'])) {
            if (isset($filter['result'][0]['id'])) {
                $user_id = $filter['result'][0]['id'];
                $contracts = Contract::where(['user_id' => $user_id])->whereIn('status', [1, 3, 4])->with('schedule')->get();
                foreach ($contracts as $contract) {
                    foreach ($contract->schedule as $schedule) {
                        $payment_date = strtotime($schedule->payment_date);
                        $now = strtotime(Carbon::now()->format('Y-m-d 23:59:59'));
                        if ($schedule->status == 0 && $payment_date <= $now) {
                            $debts += $schedule->balance;
                        }
                    }
                }
            }

            if(isset($user_id)){
                $buyer = Buyer::find($user_id);
                $vip_allowed = 1;
                if ($partner = Partner::find($user->id)) {
                    if ($partner->company->vip == 1) {
                        if ($buyer->vip) {   // если вендор сам платит за клиента, проверим его ли это клиент
                            if ($user->id != $buyer->created_by) $vip_allowed = 0;    //  1 - разрешено оформлять у этого вендора, 0 - не разрешено
                        }else{
                            $vip_allowed = 0;
                        }
                    }else{
                        if ($buyer->vip) {  // не вип вендор не может продавать вип клиентам
                            $vip_allowed = 0;
                        }
                    }
                }

                $this->result['response']['vip_allowed'] = $vip_allowed;   // vip - может купить только у вендора, кто его зарегистрировал
                $this->result['response']['black_list'] = $buyer->black_list;   // черный список
            }

        }

        //Collect data
        $this->result['response']['debs'] = $debts;   // сумма просрочки
        $this->result['response']['total'] = $filter['total'];
        $this->result['status'] = 'success';

        //Permissions
        if ($user) {
            foreach ($filter['result'] as $item) {
                $item->permissions = $this->permissions($item, $user);
            }
        }

        // 29.07 раскодирование паспорта
        foreach ($filter['result'] as $item) {
            if (!isset($item['personals']['passport_number'])) continue;
            $passport = EncryptHelper::decryptData($item['personals']['passport_number']);
            $item['personals']['passport_number'] = $passport;
        }

        //Format data
        if (isset($params['list_type']) && $params['list_type'] == 'data_tables') {
            $filter['result'] = $this->formatDataTables($filter['result']);
        }

        $this->result['data'] = $filter['result'];
        // Return data
        return $this->result();

    }

    /**
     * @param array $params
     * @return array
     */
    public function filter($params = [])
    {
        // Log::info($params);

        $orderBy = [];
        $result = [];

        //02.04 здесь универсальный метод запроса модели - query()
        // в который передаются параметры, которые формируются вручную в каждом action контроллера - ModelNameController->action()
        $query = $this->model::query();
        // if (!isset($params['type']) && isset($params['userType']) && $params['userType'] == 'buyer') {

        if (isset($params['debts']) && $params['debts'] == 1) {  // если запрос на всех просрочников (более 10 дней долга)

            $date = date('Y-m-d 23:59:00', strtotime('-10 day'));
            $query->whereHas('contracts', function ($query) use ($date) {
                $query->leftJoin('contract_payments_schedule as cps', function ($query) {
                    $query->on('cps.contract_id', 'contracts.id');
                })
                    ->where('cps.status', 0)
                    ->where('cps.payment_date', '<=', $date)
                    ->whereIn('contracts.status', [1, 3, 4]);
            });

        }

        if( !isset($params['type']) && isset($params['userType']) && $params['userType']=='buyer'){

            /*if(isset($params['phone__like'])){
                $query->with('kyc', 'history','isDebts' );
            }else {
                $query->with('kyc', 'history');
            } */
            $query->with('kyc', 'history','cardsInactive');  // с неактивными картами - 06.12.2021 (для ф-ла добавления доп карт просрочникам)

            //$query->where('company_id',null);

        }

        // список колонок в таблице
        $columns = Schema::getColumnListing($this->model->getTable());

        // продавцы
        if (isset($params['type']) && $params['type'] == 'saller') {
            //$query->has('userSallers');
            $query->where('is_saller', 1);
        }

        if (isset($params['recovery'])) {
            $query = Contract::selectRaw('contracts.id')
                ->selectRaw('contracts.user_id')
                ->selectRaw('contracts.status')
                ->selectRaw('contracts.total')
                ->selectRaw('contracts.balance')
                ->selectRaw('contracts.deposit')
                ->selectRaw('contracts.recovery')
                ->selectRaw('contracts.created_at')
                ->selectRaw('DATE_FORMAT((SELECT min(payment_date) from contract_payments_schedule where status = 0 and contract_payments_schedule.contract_id = contracts.id) ,"%Y.%m.%d") payment_date')
                ->selectRaw('contracts.date_recovery_start')
                ->selectRaw('contracts.general_company_id')
                ->selectRaw('contracts.act_status')
                ->selectRaw('contracts.imei_status')
                ->selectRaw('contracts.client_status')
                ->selectRaw('contracts.expired_days')
                ->orderBy('contracts.expired_days', 'desc')
                ->with('recoveries');

            if($params['recovery'] == [1, 2, 3, 4, 5, 6, 7] || $params['recovery'] == 7) {
                $query->where('contracts.status', 9);
            } else {
                $query->whereIn('contracts.status', [1, 3, 4]);
            }

            if ( is_array( $params['recovery'] ) ) {
                $query->whereIn('contracts.recovery', $params['recovery']);
            } else {
                $query->where('contracts.recovery', $params['recovery']);
            }

            if ( $params['action'] == 0 ) {
                $query->where('contracts.expired_days', '>', 0)->where('contracts.expired_days', '<', 30);
            } else if ( $params['action'] == 1 ) {
                $query->where('contracts.expired_days', '>', 30);
            }

            if ( isset( $params['id'] ) && ( (int) $params['id'] > 0 ) ) {
                $query->where('contracts.id', $params['id']);
            }

            unset($params['id'], $params['recovery'], $params['status']);
        } else {

            // не берем тестовые договоры
            if ( isset( $params['test'] ) && ( $params['test'] == 0 ) ) {
                $query ->whereHas('order', function ($q) {
                    $q->where('test', 0);   // тестовые не берем
                });
            }
        }

        foreach ($params as $key => $value) {

            if ( is_null($value) || ($value === '') ) {
                continue;
            }

            $buf = explode("__", $key);

            switch (count($buf)) {
                case 2:
                    $key = $buf[0];
                    $operation = $buf[1];
                    $condition = '';
                    break;
                case 3:
                    $condition = $buf[0];
                    $key = $buf[1];
                    $operation = $buf[2];
                    break;
                default:
                    $key = $buf[0];
                    $operation = '';
                    $condition = '';
                    break;
            }

            if (preg_match("/^orderBy/", $key)) {
                $orderBy[$key] = $value;
            }

            if (in_array($key, $columns)) {

                if (!is_null($value)) {

                    if (is_string($value)) {

                        // Если у поля тип Дата
                        if (preg_match("/^datetime/", $key)) {
                            $value = date('Y-m-d H:i:s', strtotime($value));
                        }

                    }

                    // условие
                    switch ($operation) {
                        case "loe": // <=
                            if (!is_array($value)) {
                                if ($condition == 'or')
                                    $query->orWhere($key, '<=', $value);
                                else
                                    $query->where($key, '<=', $value);
                            }
                            break;
                        case "moe": // >=
                            if (!is_array($value)) {
                                if ($condition == 'or')
                                    $query->orWhere($key, '>=', $value);
                                else
                                    $query->where($key, '>=', $value);
                            }
                            break;
                        case "less": // <
                            if (!is_array($value)) {
                                if ($condition == 'or')
                                    $query->orWhere($key, '<', $value);
                                else
                                    $query->where($key, '<', $value);
                            }
                            break;
                        case "more": // >
                            if (!is_array($value)) {
                                if ($condition == 'or')
                                    $query->orWhere($key, '>', $value);
                                else
                                    $query->where($key, '>', $value);
                            }
                            break;
                        case "not": // ! <>
                            if (!is_array($value)) {
                                $query->where($key, '!=', $value);
                            } elseif (is_array($value)) {
                                $query->whereNotIn($key, $value);
                            }
                            break;
                        case "like":
                            if (!is_array($value)) {
                                if ($condition == 'or')
                                    $query->orWhere($key, 'like', '%' . $value . '%');
                                else
                                    $query->where($key, 'like', '%' . $value . '%');
                            }
                            break;
                        case "between":
                            if (is_array($value)) {
                                $query->between($key, $value);
                            }
                            break;
                        default:
                            if (is_array($value)) {
                                $query->whereIn($key, $value);
                            }
                            elseif ($condition == 'or') {
                                $query->orWhere($key, $value);
                            }
                            else {
                                $query->where($key, $value);
                            }
                            break;
                    }
                }
            }

            //TODO: Запрос в relation для более подробного фильтрования. Дописать проверку на корректность ключа в дальнейшем.
            if (strpos($key, '|') > 0) {
                list($model, $param) = explode('|', $key);
                if (!is_null($value)) {
                    $query->whereHas($model, function (Builder $query) use ($param, $value) {
                        $query->where($param, '=', $value);
                    });
                }
            }

            if ($operation == 'has') {
                $query->has($key);
            }
        }

        if (isset($params['user_fio'])) {
            $fio = $params['user_fio'];
            $query->whereHas('buyer.user', function ($query) use ($fio) {
                $query->whereRaw('name LIKE "%' . $fio . '%"')
                    ->orWhereRaw('surname LIKE "%' . $fio . '%"');
            });
        }

        if (isset($params['seller_company_brand'])) {
            $companyBrand = $params['seller_company_brand'];
            $query->whereHas('company', function ($query) use ($companyBrand) {
                $query->whereRaw('brand LIKE "%' . $companyBrand . '%"');
            });
        }
        if (isset($params['phone_like']) && !empty($params['phone_like'])) {
            $phone = $params['phone_like'];
            $query->whereHas('user', function ($query) use ($phone) {
                $query->whereRaw('phone LIKE "%' . $phone . '%"');
            });
        }
        if (isset($params['name__like']) && isset($params['or__patronymic__like']) && isset($params['or__surname__like']))
        {
            $query->where(function($query) use($params)
            {
                foreach ($params['name__like'] as $name) {
                    $query->orWhere('name', 'like', "%$name%");
                    $query->orWhere('surname', 'like', "%$name%");
                    $query->orWhere('patronymic', 'like', "%$name%");
                }
                $query->where(function ($query) use ($params) {
                    foreach ($params['or__surname__like'] as $surname) {
                        $query->orWhere('name', 'like', "%$surname%");
                        $query->orWhere('surname', 'like', "%$surname%");
                        $query->orWhere('patronymic', 'like', "%$surname%");
                    }
                });
                $query->where(function ($query) use ($params)
                {
                    foreach ($params['or__patronymic__like'] as $patronymic) {
                        $query->orWhere('name', 'like', "%$patronymic%");
                        $query->orWhere('surname', 'like', "%$patronymic%");
                        $query->orWhere('patronymic', 'like', "%$patronymic%");
                    }
                });
            });
        }
        //TOTAL COUNT
        $total = $query->count();
        $debs = 0;  // сумма задожности


        if (!isset($params['total_only'])) {
            //EAGER LOAD
            $query->with($this->loadWith);
            //ORDER - сортировка
            foreach ($orderBy as $order => $column)
            {
                if(!in_array($column, $columns))
                    $query->orderByRaw("${column}");
                else
                    $query->$order($column);
            }

            if (isset($params['random']))
                $query->inRandomOrder();

            //OFFSET
            if (isset($params['offset']) && is_numeric($params['offset']))
                $query->offset($params['offset']);

            //LIMIT
            if (isset($params['limit']) && is_numeric($params['limit']))
                $query->limit($params['limit']);

            //RESULT
            $result = $query->get();


            // при оформлении договора показать (сумму?), что он в просрочке и не давать оформить ???? УММА ВСЕХ ДОЛГОВ ВСЕХ КЛИЕНТОВ
            /* if (isset($params['phone__like'])) {
                 $debts = CronUsersDelays::->select(DB::raw('SUM(balance) as balance'))->first();
                 $debs = number_format($debts->balance,2,'.',' ');
             }*/

            if (isset($params['show']) && $params['show'] == 'pinfl') {
                foreach ($result as $saller) {
                    if (!isset($saller->personals)) continue;
                    $saller->personals->pinfl = EncryptHelper::decryptData($saller->personals->pinfl);
                }
            }

        }
        return [
            'result' => $result ?? [],
            'total' => $total,
            'debs' => $debs,
        ];

    }


    /**
     * @param $innerQuery
     * @param $fields
     * @param $columns
     */
    protected function subQuery($innerQuery, $fields, $columns)
    {
        foreach ($fields as $key => $value) {
            $buf = explode("__", $key);

            switch (count($buf)) {
                case 2:
                    $key = $buf[0];
                    $operation = $buf[1];
                    $condition = '';
                    break;
                case 3:
                    $condition = $buf[0];
                    $key = $buf[1];
                    $operation = $buf[2];
                    break;
                default:
                    $key = $buf[0];
                    $operation = '';
                    $condition = '';
                    break;
            }

            if (preg_match("/^orderBy/", $key)) {
                $orderBy[$key] = $value;
            }


            if (in_array($key, $columns)) {

                if (!is_null($value)) {

                    if (is_string($value)) {

                        // Если у поля тип Дата
                        if (preg_match("/^datetime/", $key)) {
                            $value = date('Y-m-d H:i:s', strtotime($value));
                        }
                    }

                    switch ($operation) {
                        case "loe":
                            if (!is_array($value)) {
                                if ($condition == 'or')
                                    $innerQuery->orWhere($key, '<=', $value);
                                else
                                    $innerQuery->where($key, '<=', $value);
                            }
                            break;
                        case "moe":
                            if (!is_array($value)) {
                                if ($condition == 'or')
                                    $innerQuery->orWhere($key, '>=', $value);
                                else
                                    $innerQuery->where($key, '>=', $value);
                            }
                            break;
                        case "less":
                            if (!is_array($value)) {
                                if ($condition == 'or')
                                    $innerQuery->orWhere($key, '<', $value);
                                else
                                    $innerQuery->where($key, '<', $value);
                            }
                            break;
                        case "more":
                            if (!is_array($value)) {
                                if ($condition == 'or')
                                    $innerQuery->orWhere($key, '>', $value);
                                else
                                    $innerQuery->where($key, '>', $value);
                            }
                            break;
                        case "not":
                            if (!is_array($value)) {
                                $innerQuery->where($key, '!=', $value);
                            } elseif (is_array($value)) {
                                $innerQuery->whereNotIn($key, $value);
                            }
                            break;
                        case "like":
                            if (!is_array($value)) {
                                if ($condition == 'or')
                                    $innerQuery->orWhere($key, 'like', '%' . $value . '%');
                                else
                                    $innerQuery->where($key, 'like', '%' . $value . '%');
                            }
                            break;
                        case "between":
                            if (is_array($value)) {
                                $innerQuery->between($key, $value);
                            }
                            break;
                        default:
                            if (is_array($value)) {
                                $innerQuery->whereIn($key, $value);
                            } else {
                                if ($condition == 'or')
                                    $innerQuery->orWhere($key, $value);
                                else
                                    $innerQuery->where($key, $value);
                            }
                            break;
                    }
                }
            }

            //TODO: Запрос в relation для более подробного фильтрования. Дописать проверку на корректность ключа в дальнейшем.
            if (strpos($key, '|') > 0) {
                list($model, $param) = explode('|', $key);
                if (!is_null($value)) {
                    $innerQuery->whereHas($model, function (Builder $innerQuery) use ($param, $value) {
                        $innerQuery->where($param, '=', $value);
                    });
                }
            }
        }
    }

    /**
     * @param array $params
     * @return array
     */
    public function multiFilter($params = [])
    {
        $result = [];

        $query = $this->model::query();

        $columns = Schema::getColumnListing($this->model->getTable());

        if(isset($params['params'][0]['cancellation_status'])) {
            $query->whereHas('contract', function ($query) {
                $query->where('cancellation_status', 1);
            });
        }
        if(isset($params["online"]) && $params["online"] == 1) {
            $query->where('online', 1);
        }
        if(isset($params["contract_status"])) {
            $query->whereHas('contract', function ($query) use ($params) {
                $query->whereIn('status', $params["contract_status"]);
            });
        }

        foreach ($params['params'] as $group => $fields) {

            //$innerQuery = $this->model::query();

            if (!isset($fields['query_operation'])) $fields['query_operation'] = 'and';
            switch ($fields['query_operation']) {
                case 'or':
                    $query->orWhere(function ($innerQuery) use ($fields, $columns) {
                        $this->subQuery($innerQuery, $fields, $columns);
                    });
                    break;
                default:
                    $query->where(function ($innerQuery) use ($fields, $columns) {
                        $this->subQuery($innerQuery, $fields, $columns);
                    });
                    break;
            }
        }


        //TOTAL COUNT
        $total = $query->count();

        if (!isset($params['total_only'])) {
            //EAGER LOAD
            $query->with($this->loadWith);

            //ORDER
            if (isset($params['orderByDesc']))
                $query->orderByDesc($params['orderByDesc']);
            if (isset($params['orderBy']))
                $query->orderByDesc($params['orderBy']);

            if (isset($params['random']))
                $query->inRandomOrder();

            //OFFSET
            if (isset($params['offset']) && is_numeric($params['offset']))
                $query->offset($params['offset']);

            //LIMIT
            if (isset($params['limit']) && is_numeric($params['limit']))
                $query->limit($params['limit']);



            //RESULT
            $result = $query->get();


        }


        return [
            'result' => $result ?? [],
            'total' => $total
        ];
    }


    /**
     * @param $type
     * @param $text
     */
    protected function message($type, $text)
    {
        $this->result['response']['message'][] = array(
            'type' => $type,
            'text' => $text
        );
    }

    /**
     * @param $data
     */
    protected function error($data)
    {
        $this->result['response']['errors'][] = $data;
    }

    /**
     * @return array
     */
    protected function result()
    {
        // если успешный запрос и код не задан, установить его в 200
        if (
            ( $this->result['status'] === 'success' ) &&
            empty( $this->result['response']['code'] )
        ) {
            $this->result['response']['code'] = 200;
        }

        return $this->result;
    }

}
