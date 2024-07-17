<?php

namespace App\Http\Controllers\Core;

use App\Enums\CategoriesEnum;
use App\Helpers\CategoryHelper;
use App\Helpers\FileHelper;
use App\Helpers\NdsStopgagHelper;
use App\Helpers\NotificationHelper;
use App\Helpers\PushHelper;
use App\Helpers\QRCodeHelper;
use App\Helpers\SellerBonusesHelper;
use App\Helpers\SmsHelper;
use App\Http\Requests\CalculateBonusRequest;
use App\Models\Buyer;
use App\Models\BuyerPersonal;
use App\Models\CatalogProduct;
use App\Models\Company;
use App\Models\Contract;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Partner;
use App\Models\PartnerSetting;
use App\Models\Payment;
use App\Models\Order as Model;
use App\Models\Saller;
use App\Models\SellerBonus;
use App\Models\Shipping;
use App\Models\StaffPersonal;
use App\Models\User;
use App\Services\API\V3\BaseService;
use App\Services\API\V3\CatalogCategoryService;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use PDF;
use Exception;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;


class OrderController extends CoreController
{

    private $config = [
        'status' => null,
        'nds' => null
    ];

    /**
     * Fields validator
     *
     * @param array $data
     * @return Validator
     */
    /*private $validatorRules = [
        'user_id'           => ['required', 'integer'],
        //'partner_id'        => ['required', 'integer'],
        'period'            => ['required', 'integer'],
        'type'              => ['required', 'string'],
        'sms_code'          => ['required'],
    ];*/

    /**
     * OrderController constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = app(Model::class);

        //Get config
        $this->config['status'] = Config::get('test.order_status');
        $this->config['nds'] = Config::get('test.nds');

        //Eager load
        $this->loadWith = [
            'products',
            'products.info',
            'products.info.images',
            'buyer',
            'contract',
            'contract.schedule',
            'contract.nextPayment',
            'contract.activePayments',
            'contract.clientAct'
        ];
    }


    /**
     * @param array $params
     * @return array
     */
    public function filter($params = [])
    {

        /*if(isset($params['contract_status'])) {
            $ordersID = Contract::where('user_id', $params['user_id'])->where('status', $params['contract_status'])->pluck('order_id')->toArray();
            $params['id'] = $ordersID??[];
        }*/

        if (isset($params['params']))
            return parent::multiFilter($params);

        return parent::filter($params);

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
        $params = request()->all();
        $partner_id = $params['params'][0]['partner_id'][0] ?? null;

        if(isset($partner_id)) {
            if($partner_id !== $user->id) {
                $this->result['status'] = 'error';
                $this->result['response']['message'] = 'access_denied';
                return $this->result();
            }
        } else {
            $params['params'][0]['partner_id'] = $user->id; // $user == partner
        }

        if(isset($params['params'][0]['cancellation_status']) && isset($user->company_id)) {
            $ordersForCancellation = Contract::where('cancellation_status', Contract::CANCELLATION_STATUS_SENT)
                ->whereIn('company_id', function ($query) use ($user) {
                    $query->select('id')->from('companies')->where('parent_id', $user->company_id);
                    })->pluck('order_id')->toArray();

            $params['params'] = [
                [
                    'id' => $ordersForCancellation
                ]
            ];
        }

        //Filter elements
        $filter = $this->filter($params);



        // Getting 'manager_request' field to show/hide 'cancel contract' button
        $manager_request = 0;
        if (count($filter['result']) > 0) {
            $partner_settings = PartnerSetting::where('company_id', $filter['result']->first()->company_id)->first();
            $manager_request = $partner_settings->manager_request;
        }

        //Render items
        foreach ($filter['result'] as $index => $item)
            if ($user->can('detail', $item)) {
                $item->permissions = $this->permissions($item, $user);
                $item->manager_request = $manager_request;
                /*
                  * todo: Если договору больше месяца << $item->isCancelBtnShow >> сделаем 1.
                  * todo: Которое скрывает фронте кнопку << Отменить договор >>
                  * todo: 18.08 Азимжон
                  * */
                $time = strtotime($item->contract->created_at ?? 0);
                if ($time != 0) {
                    $afterMonths = date("Y-m-d", strtotime("+1 month", $time));
                    $now = Carbon::now()->format('Y-m-d');
                    $afterMonths > $now ? $item->isCancelBtnShow = 0 : $item->isCancelBtnShow = 1;
                }

                //Debt calculation
                $item->totalDebt = 0;
                if ($item->contract != null)
                    foreach ($item->contract->debts as $debt)
                        $item->totalDebt += $debt->total;

                foreach ($item->products as $product) {
                    $product['is_phone'] = (bool)count(array_intersect(CategoryHelper::getPhoneCategoryIDs(), CategoryHelper::getParentCategoryIDs($product['category_id'])));
                    if ($product->info) {
                        if (isset($product->info->images)) {
                            $preview = $product->info->images->first();
                            if ($preview) {
                                $previewPath = str_replace($preview->name, 'preview_' . $preview->name, $preview->path);
                                $product->preview = Storage::exists($previewPath) ? Storage::url($previewPath) : null;
                            }
                        }
                    }
                }
            } else {
                $filter['result']->forget($index);
            }

        //Collect data
        $this->result['response']['total'] = $filter['total'];
        $this->result['status'] = 'success';

        //Format data
        if (isset($params['list_type']) && $params['list_type'] == 'data_tables')
            $filter['result'] = $this->formatDataTables($filter['result']);

        $this->result['data'] = $filter['result'];

        //Return data
        return $this->result();
    }


    /**
     * Validate products
     *
     * @param $products
     * @return bool
     */
    private function checkProducts($products)
    {
        $check = true;

        $productValidationRules = [
            'name' => ['required', 'string', 'max:255'],
            'id' => ['nullable', 'integer'],
            'amount' => ['required', 'numeric'],
            'price' => ['required', 'numeric'],

        ];

        foreach ($products as $product) {
            $validator = $this->validator($product, $productValidationRules);
            if ($validator->fails()) {
                $this->result['response']['errors'] = $validator->errors();
                return false;
            }
        }

        return $check;
    }


    /**
     * @param array $params
     * @param bool $private
     *
     * @return array|false|string
     */
    public function calculate($params = [], $private = false)
    {
        //Если параметры не переданы, берем их из запроса
        if (count($params) == 0) $params = request()->all();

        if (!isset($params['user_id'])) {
            $this->result['status'] = 'error';
            $this->message('danger', 'billing/order.err_user_id_is_null');
            return $this->result();
        }

        //Если указан тип договора
        if (isset($params['type'])) {

            $buyer = Buyer::where('id', $params['user_id'])->with('settings')->first();

            // Проверяем, если договор на рассрочку, то должен быть указан период
            if ($params['type'] == 'direct' || ($params['type'] == 'credit' && $params['period'])) {

                $config = Config::get('test.paycoin');

                // если у покупателя приобретена общая скидка
                if (isset($buyer->settings) && $buyer->settings->paycoin_sale > 0) {
                    $sale = $buyer->settings->paycoin_sale * $config['sale'];
                } else {
                    $sale = 0;
                }

                // Заготовка результата
                $result = [
                    'price' => [
                        'total' => 0,    // Конечная цена с учетом всех параметров и кредитов
                        'shipping' => 0, // Цена доставки
                        'origin' => 0,   // Цена без кредитной наценки
                        'month' => 0,    // Ежемесячный платеж, если в кредит
                        'partner' => 0,  // Сколько должны партнеру по договору
                    ],
                    'orders' => [],
                    'amount' => 0,
                    'contract' => [
                        'payments' => [],
                    ]
                ];

                //Проверяем доставку
                $shippingCheck['status'] = 'success';
                if (isset($params['shipping']) && $params['shipping']['shipping_code'] != "") {
                    $shipController = new OrderShippingController();
                    $shippingCheck = $shipController->check($params['shipping']['shipping_code'], $params['shipping']);
                }


                if ($shippingCheck['status'] == 'success') {
                    //Подготавливаем корзину
                    if (isset($params['cart'])) {
                        $cController = new CartController();
                        $params['products'] = $cController->prepare($params)['data'];
                    }

                    //Подсчет суммы каждого договора
                    if (count($params['products']) > 0) {
                        $nds = NdsStopgagHelper::getActualNds();

                        //Формируем список договоров
                        foreach ($params['products'] as $companyID => $products) {
                            $company = Company::find($companyID);
                            $company->with('settings');

                            //Заготовка договора
                            $order = [
                                'price' => [
                                    'total' => 0,    // Конечная цена с учетом всех параметров и кредитов
                                    'shipping' => 0, // Цена доставки
                                    'origin' => 0,   // Цена без кредита,
                                    'month' => 0,
                                    'partner' => 0,
                                    'deposit' => 0,
                                ],
                                'products' => [],
                                'amount' => 0,       // Количество
                                'contract' => []     // Если кредит
                            ];

                            //Формируем договор
                            foreach ($products as &$product) {

                                //Число товаров в договоре
                                $order['amount'] += $product['amount'];
                                $order['price']['origin'] += $product['price'] * $product['amount'];

                                $price = $product['price'] * $product['amount'];

                                // если есть скидка на конкретный товар, сразу считаем со скидкой
                                if (isset($product['price_discount']) && $product['price_discount'] > 0) {
                                    $price -= $price * $product['price_discount'] / 100;
                                    $order['price']['origin'] -= $order['price']['origin'] * $product['price_discount'] / 100;
                                    $month_discount = 0;
                                } else {
                                    // если товар без скидки, смотрим если есть другие скидки
                                    if ($params['period'] > 12) {
                                        $month_discount = $company->settings['discount_12'] / 100; // берем тот же коэфф , что для 12 месяцев - 44%
                                    } else {
                                        $month_discount = $company->settings['discount_' . $params['period']] / 100;
                                    }

                                    $price -= $price * $month_discount;
                                }

                                // Сумма конкретного договора УЖЕ со скидками
                                $order['price']['total'] += $price;
                                // цена конкретного товара УЖЕ со скидкой

                                if (isset($company->reverse_calc) && $company->reverse_calc == 1) {
                                    $product['price'] = round(($product['price'] - ($product['price'] * $month_discount)) / Config::get('test.rvs'), 2);
                                } else {
                                    $product['price'] = round($product['price'] - ($product['price'] * $month_discount), 2);
                                }
                            }

                            // Если наш сотрудник && период рассрочки 12, то особая наценка
                            $month_markup = self::getMarkup($company, $params['user_id'] ?? 0, $params['period']);

                            // цена товара УЖЕ со скидкой (product_price)
                            $order['products'] = $products;

                            //Накидываем доставку
                            if (isset($params['shipping']) && $params['shipping']['shipping_code'] != "") {
                                $shippingParams = [
                                    'products' => $products,
                                    'address' => $params['shipping']['address']
                                ];

                                $order['price']['shipping'] = $shipController->calculate($params['shipping']['shipping_code'], $shippingParams)['data']['total'];
                            }
                            //Сумма конкретного договора для продавца
                            $order['price']['partner'] += $order['price']['origin'] - ($order['price']['origin'] * $month_discount);  // со скидкой

                            //ЕСЛИ КРЕДИТ
                            if ($params['type'] == 'credit') {

                                // если клиент приобрел скидку, вычитаем из маржи
                                $month_markup = ($month_markup - $sale) / 100;

                                // обратная калькуляция процентов (уже заложены в цене)
                                $rvs = Config::get('test.rvs');
                                if (isset($company->reverse_calc) && $company->reverse_calc == 1) {  // если это обратные проценты
                                    $order['price']['origin'] = round($order['price']['origin'] / $rvs, 2);
                                    $order['price']['total'] = $order['price']['total'] / $rvs;
                                    $order['price']['partner'] = round($order['price']['partner'] / $rvs, 2); // вычитаем проценты из партнерской цены
                                }

                                // ДЕПОЗИТНЫЙ ВЗНОС
                                // тут вычислим если хватает на депозитный взнос
                                if ($buyer && $order['price']['origin'] >= $buyer->settings->balance) {
                                    $order['price']['deposit'] = $order['price']['origin'] - $buyer->settings->balance;

                                    // if there was a deposit, please minus it
                                    if ($order['price']['deposit'] > 0) {
                                        // если без ндс
                                        $order['price']['total'] -= $order['price']['deposit'];
                                    }
                                }

                                //Конечная цена с учетом кредитной наценки
                                if ($company->reverse_calc !== 1) {
                                    // promotion - проценты и ндс уже заложены в цене
                                    if (isset($company->promotion) && $company->promotion == 1) {  // если это трехмесячная акция
                                        if ($params['period'] == 3) {
                                            $order['price']['total'] = $order['price']['origin'] - $order['price']['deposit'];
                                        } else {
                                            // для всех обычных месяцев
                                            $order['price']['total'] += $order['price']['total'] * $month_markup;
                                        }
                                    } else {
                                        // для всех обычных случаев
                                        $order['price']['total'] += $order['price']['total'] * $month_markup;
                                    }
                                } else {
                                    $order['price']['total'] += $order['price']['total'] * $month_markup;
                                }

                                //Если партнер без НДС, накидываем сверху
                                if (!$company->settings['nds']) {

                                    // if there was a deposit, please minus it
                                    if ($order['price']['deposit'] > 0) {
                                        $order['price']['total'] += $order['price']['deposit'];  // прибавить депозит
                                        $order['price']['total'] += $order['price']['total'] * $nds;  // накинуть ндс
                                        $order['price']['total'] -= $order['price']['deposit']; // отнять депозит
                                    } else {
                                        $order['price']['total'] += $order['price']['total'] * $nds;
                                    }

                                }

                                //Ежемесячные платежи

                                //если это обратные проценты, вычесть депозит

                                $paymentMonthly = round($order['price']['total'] / $params['period'], 2);
                                $paymentMonthlyOrigin = round($order['price']['origin'] / $params['period'], 2);
                                $paymentMonthlyDeposit = round(($order['price']['origin'] - $order['price']['deposit']) / $params['period'], 2);
                                $priceOrigin = $order['price']['origin'];

                                //if DEPOSIT - MINUS IT PLEASE
                                if ($order['price']['deposit'] > 0) {
                                    $paymentMonthlyOrigin = $paymentMonthlyDeposit;
                                    $priceOrigin = $order['price']['origin'] - $order['price']['deposit'];
                                }
                                $payments = [];
                                for ($i = 0; $i < $params['period']; $i++) {

                                    if ($i < ($params['period'] - 1)) {
                                        $payments[] = [
                                            'total' => $paymentMonthly,
                                            'origin' => $paymentMonthlyOrigin,
                                        ];
                                    } else {
                                        $payments[] = [
                                            'total' => round($order['price']['total'] - $paymentMonthly * ($params['period'] - 1), 2),
                                            'origin' => round($priceOrigin - $paymentMonthlyOrigin * ($params['period'] - 1), 2)
                                        ];
                                    }

                                }
                                $order['contract']['payments'] = $payments;
                                $order['price']['month'] = $payments[0]['total'];
                            } else {
                                if ($company->settings['discount_direct'] != null) {
                                    $order['price']['total'] -= $order['price']['total'] * ($company->settings['discount_direct'] / 100);
                                }

                                if (isset($params['shipping']) && $params['shipping']['shipping_code'] != "")
                                    $order['price']['total'] += $order['price']['shipping'];

                                $order['price']['origin'] = round($order['price']['origin'], 2);
                                $order['price']['total'] = round($order['price']['total'], 2); // 27,04 то же самое что и сверху непонятно почему = =
                            }
                            $result['orders'][$companyID] = $order;
                        }
                    }

                    // Выводим общее
                    foreach ($result['orders'] as $companyID => $order) {
                        $result['amount'] += $order['amount'];
                        $result['price']['total'] += $order['price']['total'];
                        $result['price']['origin'] += $order['price']['origin'];
                        $result['price']['shipping'] += $order['price']['shipping'];
                        $result['price']['partner'] += $order['price']['partner'];
                        $result['price']['month'] += $order['price']['month'];

                        //Сумма ежемесячных платежей по всем договорам для вывода
                        if ($params['type'] == 'credit') {
                            foreach ($order['contract']['payments'] as $i => $payment) {
                                if (!isset($result['contract']['payments'][$i]))
                                    $result['contract']['payments'][$i] = [
                                        'total' => 0,
                                        'origin' => 0
                                    ];
                                $result['contract']['payments'][$i]['total'] += $payment['total'];
                                $result['contract']['payments'][$i]['origin'] += $payment['origin'];
                            }
                        }
                    }

                    $result['price']['total'] = round($result['price']['total']);
                    $result['price']['origin'] = round($result['price']['origin']);
                    $result['price']['shipping'] = round($result['price']['shipping']);
                    $result['price']['partner'] = round($result['price']['partner']);
                    $result['price']['month'] = round($result['price']['month']);

                    $this->result['status'] = 'success';
                    $this->result['data'] = $result;
                } else {
                    $this->result = $shippingCheck;
                }

            } else {
                $this->result['status'] = 'error';
                $this->message('danger', 'billing/order.err_period_is_null');
            }
        } else {
            $this->result['status'] = 'error';
            $this->message('danger', 'billing/order.err_order_type_is_null');
        }
        return $private ? $this->result : $this->result();
    }


    /**
     * buyer_id неизвестен, общая калькуляция, но со скидками вендора
     * @param array $params
     * @param bool $private
     *
     * @return array|false|string
     */
    public function MarketPlaceCalculate($params = [], $private = false)
    {

        //Если параметры не переданы, берем их из запроса
        if (count($params) == 0) $params = request()->all();

        //Если указан тип договора
        if (isset($params['type'])) {

            //Проверяем, если договор на рассрочку, то должен быть указан период
            if ($params['type'] == 'direct' || ($params['type'] == 'credit' && $params['period'])) {

                $config = Config::get('test.paycoin');

                //Заготовка результата
                $result = [
                    'price' => [
                        'total' => 0,   //Конечная цена с учетом всех параметров и кредитов
                        'shipping' => 0,   //Цена доставки
                        'origin' => 0,   //Цена без кредитной наценки
                        'month' => 0,   //Ежемесячный платеж, если в кредитб
                        'partner' => 0,   //Сколько должны партнеру по договору
                    ],
                    'orders' => [],
                    'amount' => 0,
                    'contract' => [
                        'payments' => [],
                    ]
                ];

                //Проверяем доставку
                $shippingCheck['status'] = 'success';
                if (isset($params['shipping']) && $params['shipping']['shipping_code'] != "") {
                    $shipController = new OrderShippingController();
                    $shippingCheck = $shipController->check($params['shipping']['shipping_code'], $params['shipping']);
                }

                if ($shippingCheck['status'] == 'success') {
                    //Подготавливаем корзину
                    if (isset($params['cart'])) {
                        $cController = new CartController();
                        $params['products'] = $cController->prepare($params)['data'];
                    }

                    //Подсчет суммы каждого договора
                    if (count($params['products']) > 0) {
                        $nds = NdsStopgagHelper::getActualNds();

                        //Формируем список договоров
                        foreach ($params['products'] as $companyID => $products) {
                            $company = Company::find($companyID);
                            $company->with('settings');

                            //Заготовка договора
                            $order = [
                                'price' => [
                                    'total' => 0,   //Конечная цена с учетом всех параметров и кредитов
                                    'shipping' => 0,   //Цена доставки
                                    'origin' => 0,   //Цена без кредита,
                                    'month' => 0,
                                    'partner' => 0,
                                    'deposit' => 0,
                                ],
                                //'products'  => $products,
                                'products' => [],
                                'amount' => 0,       //Количество
                                'contract' => []       //Если кредит
                            ];

                            //Формируем договор
                            foreach ($products as &$product) {

                                //Число товаров в договоре
                                $order['amount'] += $product['amount'];
                                $order['price']['origin'] += $product['price'] * $product['amount'];

                                $price = $product['price'] * $product['amount'];

                                // если есть скидка на конкретный товар, сразу считаем со скидкой
                                if (isset($product['price_discount']) && $product['price_discount'] > 0) {
                                    $price -= $price * $product['price_discount'] / 100;
                                    $order['price']['origin'] -= $order['price']['origin'] * $product['price_discount'] / 100;
                                    $month_discount = 0;
                                } else {
                                    // если товар без скидки, смотрим если есть другие скидки
                                    if ($params['period'] > 12) {
                                        $month_discount = $company->settings['discount_12'] / 100; // берем тот же коэфф , что для 12 месяцев - 44%
                                    } else {
                                        $month_discount = $company->settings['discount_' . $params['period']] / 100;
                                    }
                                    $price -= $price * $month_discount;
                                }

                                //Сумма конкретного договора УЖЕ со скидками
                                $order['price']['total'] += $price;
                                // цена конкретного товара УЖЕ со скидкой
                                $product['price'] = $product['price'] - ($product['price'] * $month_discount);

                            }

                            // цена товара УЖЕ со скидкой (product_price)
                            $order['products'] = $products;

                            //Накидываем доставку
                            if (isset($params['shipping']) && $params['shipping']['shipping_code'] != "") {
                                $shippingParams = [
                                    'products' => $products,
                                    'address' => $params['shipping']['address']
                                ];

                                $order['price']['shipping'] = $shipController->calculate($params['shipping']['shipping_code'], $shippingParams)['data']['total'];
                            }
                            //Сумма конкретного договора для продавца
                            //$order['price']['partner'] += $order['price']['origin'];
                            $order['price']['partner'] += $order['price']['origin'] - ($order['price']['origin'] * $month_discount);  // со скидкой

                            //Округляем до 2 знаков
                            $order['price']['origin'] = round($order['price']['origin'], 2);
                            $order['price']['total'] = round($order['price']['total'], 2);  // со скидкой
                            $order['price']['partner'] = round($order['price']['partner'], 2);

                            //ЕСЛИ КРЕДИТ
                            if ($params['type'] == 'credit') {

                                $month_markup = $company->settings['markup_' . $params['period']] / 100;

                                //Округляем до 2 знаков
                                $order['price']['origin'] = round($order['price']['origin'], 2);
                                $order['price']['total'] = round($order['price']['total'], 2);
                                $order['price']['partner'] = round($order['price']['partner'], 2);

                                // обратная калькуляция процентов (уже заложены в цене)
                                if (isset($company->reverse_calc) && $company->reverse_calc == 1) {  // если это обратные проценты
                                    $rvs = Config::get('test.rvs');
                                    $order['price']['partner'] = round($order['price']['partner'] / $rvs); // вычитаем проценты из партнерской цены
                                }

                                //Конечная цена с учетом кредитной наценки
                                if (isset($company->reverse_calc) && $company->reverse_calc == 1) {  // не ставим наценку, если это обратные проценты
                                    $order['price']['total'] = $order['price']['total'];
                                } else {
                                    $order['price']['total'] += ($order['price']['total'] * $month_markup);
                                }

                                $order['price']['total'] = round($order['price']['total']);

                                //Если партнер без НДС, накидываем сверху
                                if (!$company->settings['nds']) {
                                    $order['price']['total'] += $order['price']['total'] * $nds;
                                }

                                //Ежемесячные платежи

                                //если это обратные проценты, вычесть депозит
                                if (isset($company->reverse_calc) && $company->reverse_calc == 1) {
                                    $paymentMonthly = round(($order['price']['origin'] - $order['price']['deposit']) / $params['period'], 2);
                                } else {
                                    $paymentMonthly = round($order['price']['total'] / $params['period'], 2);
                                }

                                $paymentMonthlyOrigin = round($order['price']['origin'] / $params['period'], 2);
                                $paymentMonthlyDeposit = round(($order['price']['origin'] - $order['price']['deposit']) / $params['period'], 2);
                                $priceOrigin = $order['price']['origin'];

                                $payments = [];
                                for ($i = 0; $i < $params['period']; $i++) {

                                    if ($i < ($params['period'] - 1)) {
                                        $payments[] = [
                                            'total' => $paymentMonthly,
                                            'origin' => $paymentMonthlyOrigin,
                                        ];
                                    } else {
                                        $payments[] = [
                                            'total' => round($order['price']['total'] - $paymentMonthly * ($params['period'] - 1), 2),
                                            'origin' => round($priceOrigin - $paymentMonthlyOrigin * ($params['period'] - 1), 2)
                                        ];
                                    }

                                }

                                $order['contract']['payments'] = $payments;
                                $order['price']['month'] = $payments[0]['total'];
                            } else {
                                if ($company->settings['discount_direct'] != null) {
                                    $order['price']['total'] -= $order['price']['total'] * ($company->settings['discount_direct'] / 100);
                                }

                                if (isset($params['shipping']) && $params['shipping']['shipping_code'] != "")
                                    $order['price']['total'] += $order['price']['shipping'];

                                $order['price']['origin'] = round($order['price']['origin'], 2);
                                $order['price']['total'] = round($order['price']['total'], 2); // 27,04 то же самое что и сверху непонятно почему = =
                            }

                            $result['orders'][$companyID] = $order;
                        }
                    }

                    //Выводим общее
                    foreach ($result['orders'] as $companyID => $order) {
                        $result['amount'] += $order['amount'];
                        $result['price']['total'] += $order['price']['total'];
                        $result['price']['origin'] += $order['price']['origin'];
                        $result['price']['shipping'] += $order['price']['shipping'];
                        $result['price']['partner'] += $order['price']['partner'];
                        $result['price']['month'] += $order['price']['month'];

                        //Сумма ежемесячных платежей по всем договорам для вывода
                        if ($params['type'] == 'credit') {
                            foreach ($order['contract']['payments'] as $i => $payment) {
                                if (!isset($result['contract']['payments'][$i]))
                                    $result['contract']['payments'][$i] = [
                                        'total' => 0,
                                        'origin' => 0
                                    ];
                                $result['contract']['payments'][$i]['total'] += $payment['total'];
                                $result['contract']['payments'][$i]['origin'] += $payment['origin'];
                            }
                        }
                    }

                    $result['price']['total'] = round($result['price']['total']);
                    $result['price']['origin'] = round($result['price']['origin']);
                    $result['price']['shipping'] = round($result['price']['shipping']);
                    $result['price']['partner'] = round($result['price']['partner']);
                    $result['price']['month'] = round($result['price']['month']);

                    $this->result['status'] = 'success';
                    $this->result['data'] = $result;
                } else {
                    $this->result = $shippingCheck;
                }

            } else {
                $this->result['status'] = 'error';
                $this->message('danger', 'billing/order.err_period_is_null');
            }
        } else {
            $this->result['status'] = 'error';
            $this->message('danger', 'billing/order.err_order_type_is_null');
        }

        return $private ? $this->result : $this->result();
    }

    /**
     * Creating new order
     *
     * @param array $params
     * @return array
     * @throws \JsonException
     */
    public function add($params = []): array
    {
        $user = Auth::user();
        Log::info('add order');

        if (count($params) === 0)
            $params = request()->all();

        Log::info($params);

        if ($user->can('add', Model::class) && Company::where(['id' => $user->company_id, 'status' => 1])->first()) {

            $calculation = $this->calculate($params, $params['user_id'], true);
            $origin = $calculation['data']['price']['origin'];  // чистая цена

            if ($calculation['status'] == 'success') {
                $calculation = $calculation['data'];
                $buyer = Buyer::where(['id' => $params['user_id'], 'status' => 4])->first();
                $nds = NdsStopgagHelper::getActualNds();

                if ($buyer) {
                    // проверка на черный список
                    if ($buyer->black_list) {
                        $this->result['status'] = 'error';
                        $this->message('danger', __('billing/order.err_black_list'));
                        return $this->result;
                    }

                    //  лимит
                    foreach ($calculation['orders'] as $vendorID => $orderItem) {
                        $company = Company::find($vendorID); // нужна компания
                    }

                    $creditLimit = $buyer->settings->balance + $buyer->settings->personal_account;

                    if ($buyer->settings->balance == 0) $creditLimit = 0;

                    // График оплаты начиная с заданного числа, по умолчанию 10
                    //$d_graf = $params['plan_graf'] > 0 ? $params['plan_graf'] : 10;

                    $d_graf = 1;  // всегда 1

                    //Проверка лимита покупателя - если срок на 24 месяца, сумма товаров должна быть больше 8 млн (задается в админке) - 23.12.2021
                    if ($params['type'] == 'credit' && $params['period'] == 24) {
                        if ($calculation['price']['origin'] < (int)$company->settings->limit_for_24) {
                            $this->result['status'] = 'error';
                            $this->message('danger', __('billing/order.err_limit'));
                        }
                    }

                    // сумма предоплаты по акции - 3мес с предоплатой
                    $promotion_percent = $company->settings->promotion_percent / 100;
                    $promotion_prepayment = $calculation['price']['origin'] * $promotion_percent;

                    // если это акция, проверим хватает ли на ЛС денег для предоплаты
                    if ($company->promotion == 1 && $params['period'] == 3 && $buyer->settings->personal_account < ($promotion_prepayment + $orderItem['price']['deposit'])) {
                        $this->result['status'] = 'error';
                        $this->message('danger', __('billing/order.err_promotion_percent'));
                        return $this->result();
                    }

                    // временная корректировка (9.3132257461548E-10 ) - поднимем лимит на 0.1
                    if ($params['type'] == 'credit' && ($calculation['price']['origin'] > $creditLimit + 0.1)) {
                        $this->result['status'] = 'error';
                        $this->message('danger', __('billing/order.err_limit'));
                    } else {

                        $config = Config::get('test.paycoin');

                        // если у покупателя приобретена скидка
                        if ($buyer->settings->paycoin_sale > 0) {
                            $sale = $buyer->settings->paycoin_sale * $config['sale'];
                        } else {
                            $sale = 0;
                        }

                        //Сохраняем все договоры
                        foreach ($calculation['orders'] as $vendorID => $orderItem) {
                            $company = Company::find($vendorID);
                            $company->with('settings');

                            // если ввели телефон продавца, начислить ему бонусы для оплаты UPAY сервисов
                            if (isset($params['seller_id'])) {
                                $seller = Buyer::find($params['seller_id']);
                            }

                            //Create & save order
                            $order = new Model();
                            $order->partner_id = $company->user->id;
                            $order->company_id = $company->id;
                            $order->user_id = $params['user_id'];
                            $order->total = $orderItem['price']['total'];
                            $order->partner_total = $orderItem['price']['partner'];
                            $order->credit = $order->partner_total;
                            $order->debit = ($params['type'] == 'direct' ? round($order->partner_total * Config::get('test.direct_order_reward'), 2) : 0);
                            $order->status = (isset($params['cart']) ? 1 : 0);
                            $order->online = (isset($params['online']) ? $params['online'] : 0);  // все договоры от маркетплейса

                            //TODO: Совместимость АПИ
                            if (isset($params['created_at']))
                                $order->status = 0;

                            //Информация о доставке, если есть
                            if (isset($params['shipping']) && isset($params['shipping']['address'])) {
                                $order->shipping_code = $params['shipping']['shipping_code'];
                                $order->shipping_price = $orderItem['price']['shipping'];

                                $order->region = $params['shipping']['address']['region'];
                                $order->city = $params['shipping']['address']['city'];
                                $order->area = $params['shipping']['address']['area'];
                                $order->address = $params['shipping']['address']['address'];
                            }

                            $order->save();

                            $notifyData = [
                                'order' => $order,
                                'buyer' => $buyer->user,
                                'partner' => $company->user,
                                'status' => null,
                            ];
                            NotificationHelper::orderCreated($notifyData, app()->getLocale());

                            //Сохраняем ID текущего договора в результирующий массив
                            $this->result['data']['orders'][$order->company_id]['id'] = $order->id;

                            $summ = 0;

                            // Если наш сотрудник && период рассрочки 12, то особая наценка
                            $month_markup = self::getMarkup($company, $params['user_id'] ?? 0, $params['period']);

                            // если клиент приобрел скидку, отнимаем ее из маржи
                            $month_markup = ($month_markup - $sale) / 100;

                            //Create & save order products
                            foreach ($orderItem['products'] as $productItem) {

                                // чистая цена
                                $price = $productItem['price'];
                                // если есть скидка на конкретный товар, сразу считаем со скидкой
                                if (isset($productItem['price_discount']) && $productItem['price_discount'] > 0) {
                                    $price -= $price * $productItem['price_discount'] / 100;

                                }

                                // Калькуляция новых полей


                                $imei = !empty($productItem['imei']) ? ' IMEI: ' . $productItem['imei'] : '';
                                $original_imei = !empty($productItem['original_imei']) ? ' IMEI: ' . $productItem['original_imei'] : '';
                                $product = new OrderProduct();
                                $product->order_id = $order->id;
                                $product->name = str_replace(';', ',', $productItem['name'] . $imei);
                                $product->original_name = isset($productItem['original_name']) ? str_replace(';', ',', $productItem['original_name'] . $original_imei) : null;
                                $product->original_imei = $productItem['original_imei'] ?? null;
                                $product->price = $price; // $productItem['price'];
                                $product->category_id = $productItem['category'] ?? null;
                                $product->imei = $productItem['imei'] ?? null;

                                // 29.07.2022
                                $product->unit_id = $productItem['unit_id'] ?? null;

                                //Корректируем стоимость с учетом кредита
                                if ($params['type'] == 'credit') {
                                    //Скидка на товар
                                    //если это обратные проценты, убрать процент, который уже заложен в цене
                                        $product->price_discount = $productItem['price'];

                                        // депозит рассчитывается только если не было заложенных процентов в изначальную цену
                                        // if there was a deposit
                                        if ($orderItem['price']['deposit'] > 0) {

                                            //получаем процент одного продукта от общей стоимости
                                            $clear_item_per = $product->price / $order->partner_total;

                                            // разбить депозит
                                            $deposit_per = $orderItem['price']['deposit'] * $clear_item_per;

                                            // наша доля
                                            $our_price = $product->price - $deposit_per;

                                            //  Наценка на нашу долю
                                            $our_price += $our_price * $month_markup; //($company->settings['markup_'.$params['period']]/100);

                                            //Сумма конкретного договора
                                            $product->price = $our_price + $deposit_per;
                                            //НДС, если необходимо
                                            if (!$company->settings['nds'] && $product['id'] == null) {
                                                $product->price = $product->price * (NdsStopgagHelper::getActualNdsPlusOne());
                                            }

                                        } else {
                                            //НДС, если необходимо
                                            if (!$company->settings['nds'] && $product['id'] == null) {
                                                $product->price = $product->price * (NdsStopgagHelper::getActualNdsPlusOne());
                                            }

                                            $product->price = round($product->price * (1 + $month_markup), 2);
                                        }

                                    $summ += $product->price * $productItem['amount'];  // сумма всех товаров с наценкой

                                } else {

                                    //НДС, если необходимо
                                    if (!$company->settings['nds'] && $product['id'] == null)
                                        $product->price = $product->price * (NdsStopgagHelper::getActualNdsPlusOne());

                                    $product->price_discount = round($product->price_discount);
                                    $product->price = round($product->price);
                                }

                                $product->product_id = $productItem['id'] ?? null;
                                $product->amount = $productItem['amount'];
                                $product->weight = $productItem['weight'] ?? 0;
                                $product->vendor_code = $productItem['vendor_code'] ?? '';
                                $product->save();

                                // TODO Need to refactor  [whole CLASS!!!!]
                                if($company->settings['nds']) {
                                    $product->original_price = round($product->price_discount / NdsStopgagHelper::getActualNdsPlusOne(), 2);
                                    $product->total_nds      = round(($product->price_discount * $product->amount) /
                                                                     NdsStopgagHelper::getActualNdsPlusOne() * NdsStopgagHelper::getActualNds(), 2);
                                } else {
                                    $product->original_price = round($product->price_discount, 2);
                                    $product->total_nds = 0;
                                }
                                $product->original_price_client = round($product->source_price, 2);
                                $product->total_nds_client = round($product->total_nds_sum, 2);
                                $product->used_nds_percent = config('test.nds');
                                $product->save();

                                //Decrement product $quantity
                                if ($product->product_id && $company->settings->check_quantity == 1)
                                    CatalogProductController::quantity($product->product_id, 'decrement', $productItem['amount']);
                            }


                            // если есть разница между суммой товаров и конечной ценой, корректируем последний товар
                            if ($orderItem['price']['total'] + $orderItem['price']['deposit'] - $summ != 0) {
                                $product->price += abs($orderItem['price']['total'] + $orderItem['price']['deposit'] - $summ) / $product->amount;
                                $product->save();
                            }

                            if ($params['type'] == 'credit') {
                                $contractController = new ContractController();

                                //Create contract
                                $paramsContract = [
                                    'user_id' => $order->user_id,
                                    'total' => $order->total,
                                    'period' => $params['period'],
                                    'deposit' => (isset($orderItem['price']['deposit'])) ? $orderItem['price']['deposit'] : 0,
                                    'partner_id' => $order->partner_id,
                                    'company_id' => $order->company_id,
                                    'order_id' => $order->id,
                                    'confirmation_code' => $params['sms_code'] ?? null,
                                    'offer_preview' => $params['offer_preview'] ?? null,
                                    'payments' => $orderItem['contract']['payments'],
                                    'status' => (isset($params['cart']) ? 0 : 1),
                                    'd_graf' => $d_graf,
                                    'ox_system' => (isset($params['ox_system']) ? $params['ox_system'] : 0)  // все договоры от ox system **
                                ];
                                //TODO: Совместимость АПИ
                                if (isset($params['created_at']))
                                    $paramsContract['created_at'] = $params['created_at'];

                                $contract = $contractController->add($paramsContract, true);

                                // Если есть продавец магазина, регистрируем ему и его менеджерам бонусы с продажи
                                if (isset($seller)) {

                                    $originalBonusAmount = SellerBonusesHelper::calculateBonus($seller->id, $origin);
                                    $sellerBonusAmount = $originalBonusAmount * $seller->seller_bonus_percent / 100;
                                    SellerBonusesHelper::registerBonus($seller->id, $contract['data']['id'], $sellerBonusAmount);

                                    if (count($seller->bonusSharers) > 0) {
                                      foreach ($seller->bonusSharers as $bonusSharer) {
                                        if ($bonusSharer->sharer_id && $bonusSharer->percent) {
                                          $sharerBonusAmount = $originalBonusAmount * $bonusSharer->percent / 100;
                                          SellerBonusesHelper::registerBonus($bonusSharer->sharer_id, $contract['data']['id'], $sharerBonusAmount);
                                        }
                                      }
                                    }
                                }
                                // prefix_act - порядковый номер счет фактуры вендора
                                $ct = Contract::where('id', $contract['data']['id'])->first();
                                $ct->prefix_act = Contract::where('partner_id', $order->partner_id)->where('id', '<=', $contract['data']['id'])->count();
                                $ct->save();

                                if ( $contract['status'] === 'success' ) {

                                    //Calculate buyer balance
                                    $this->result['data']['contract']['id'] = $contract['data']['id'];

                                    $buyer->settings->save();

                                } else {
                                    $this->result['status'] = 'error';
                                    $this->message('danger', __('billing/order.err_cant_create_contract') . ' ' . $company->name);

                                    foreach ($contract['response']['errors']->all() as $message) {
                                        $this->message('danger', $message);
                                    }

                                    Log::channel('contracts')->info('Ошибка при создании конктракта, не удалось создать контракт');
                                    Log::channel('contracts')->info($company->name . " - " . $message);

                                }
                            }
                        }

                        if ( $this->result['status'] !== 'error' ) {

                            Log::channel('contracts')->info("Контракт " . $contract['data']['id'] . " создан  ");

                            $this->result['status'] = 'success';
                            $this->result['data']['order_id'] = $order->id;
                            $this->result['data']['contract_id'] = $contract['data']['id'];
                            $this->message('success', __('billing/order.txt_created'));

                            //SMS notification to vendor and manager (if $order->online == 1)
                            if ($order->online == 1) {
                                $msg = "resusNasiya / Hurmatli sotuvchi, siz buyurtma oldingiz. Iltimos, 5-12 soat ichida qayta ishlang. Hurmat bilan resusNasiya. Tel: " . callCenterNumber(2);

                                if ($company->phone) {
                                    SmsHelper::sendSms($company->phone, $msg);
                                } else {
                                    Log::info("SMS not sent, phone number not found");
                                }

                                if ($company->manager_phone) {
                                    SmsHelper::sendSms($company->manager_phone, $msg);
                                } else {
                                    Log::info("SMS not sent, manager phone number not found");
                                }
                            }

                            // 15.07 - тут будем создавать pdf

                            $result = $this->detail($order->id);
                            $result['data']['status_list'] = Config::get('test.order_status');

                            if ($result['data']['order']->contract) {

                                $contact_folder = 'contract/';
                                $folder = $contact_folder . $result['data']['order']->contract->id;

                                # Offer .PDF
                                $uniqueID = md5(time());
                                $namePdf = 'vendor_offer_' . $result['data']['order']->contract->id . '.pdf';

                                // Generate QR-code
                                ob_start();
                                QRCodeHelper::url(FileHelper::sourcePath() . $folder . '/' . $namePdf);
                                $imagedata = ob_get_clean();

                                Log::info(
                                    "QR-code containing a PDF-act path was created, " .
                                    "something like this: " . FileHelper::sourcePath() . $folder . '/' . $namePdf
                                );


                                $result['data']['qrcode'] = '<img src="data:image/png;base64,' . base64_encode($imagedata) . '"/>';

                                $link = "$folder/" . $uniqueID . "/$namePdf";  // $link = 'contract/123456/md5(time())/vendor_offer_123456.pdf';

                                if (!FileHelper::exists($link)) {  // $link = 'contract/123456/md5(time())/vendor_offer_123456.pdf';
                                    FileHelper::generateAndUploadPDF($link, 'billing.order.parts.offer_pdf', $result['data']);
                                    Log::info('vendor_pdf create ' . $link);
                                }
                                Log::info("ContractID: " . $result['data']['order']->contract->id . ". Выше должна была создаться запись 'vendor_pdf create'.\n" .
                                    "Если отсутствует, то PDF-файл не был создан, так как файл с таким именем и расположением уже имелся."
                                );
                                $result['data']['offer_pdf'] = '/storage/contract/' . $result['data']['order']->contract->id . '/' . $uniqueID . '/' . $namePdf;
                                $this->result['data']['offer_pdf'] = $result['data']['offer_pdf'];

                                # Account .PDF
                                $namePdf = 'buyer_account_' . $result['data']['order']->contract->id . '.pdf';

                                $link = "$folder/" . $uniqueID . "/$namePdf"; // $link = 'contract/123456/md5(time())/buyer_account_123456.pdf';

                                if ( !FileHelper::exists($link) ) {
                                    $fileInfo = pathinfo($link);
                                    FileHelper::generateAndUploadPDF($link, 'billing.order.parts.account_pdf', $result['data']);
                                    Log::info('buyer_account_pdf create ' . $link);

                                    $file = new \App\Models\File();
                                    $file->element_id = $result['data']['order']->contract->id;
                                    $file->model = 'contract';
                                    $file->type = 'contract_pdf';
                                    $file->name = $fileInfo['basename'];
                                    $file->path = $link;
                                    $file->language_code = $params['language_code'] ?? null;
                                    $file->user_id = $params['partner_id'];
                                    $file->doc_path = 1;
                                    $file->save();
                                }
                                Log::info("ContractID: " . $result['data']['order']->contract->id . ". Выше должна была создаться запись 'buyer_account_pdf create'.\n" .
                                    "Если отсутствует, то PDF-файл не был создан, так как файл с таким именем и расположением уже имелся.\n" .
                                    "Также в таблице Files должна была появиться запись с model: 'contract', type: 'contract_pdf', и element_id: " . $result['data']['order']->contract->id
                                );

                                $result['data']['account_pdf'] = '/storage/contract/' . $result['data']['order']->contract->id . '/' . $uniqueID . '/' . $namePdf;
                                $this->result['data']['account_pdf'] = '/storage/contract/' . $result['data']['order']->contract->id . '/' . $uniqueID . '/' . $namePdf;
                                $this->result['data']['full_path_account_pdf'] = Config::get('test.sftp_file_server_domain').'storage/contract/' . $result['data']['order']->contract->id . '/' . $uniqueID . '/' . $namePdf;

                                $buyerInfo = Buyer::getInfo($buyer->id); //  buyer_id
                                Log::channel('contracts')->info('buyerInfo');
                                Log::channel('contracts')->info($buyerInfo);
                                return $this->result();
                            }
                        }
                    }
                } else {
                    //Покупатель не найден
                    $this->result['status'] = 'error';
                    $this->message('danger', __('billing/order.err_buyer_not_found'));
                }
            } else {
                //Если при расчетах ошибка, то расчеты уже содержат информацию о них
                $this->result = $calculation;
            }
        } else if (Company::where(['id' => $user->company_id, 'status' => 0])->first()) {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.user_is_blocked'));
        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('app.err_access_denied!!'));
        }

        return $this->result();
    }


    /**
     * Make offer preview if credit
     *
     * печать акта (вместо пдф)
     *
     * @param array $params
     * @return array|false|string
     */
    public function printAct($id)
    {

        $contract = Contract::where('id', $id)->select('id')->first();
        $order = Order::where('id', $contract->id)->first();

        return view('billing.order.parts.print_account_pdf', compact('order'));
    }


    /**
     * @param $id
     * @param array $with
     *
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object
     */
    protected function single($id, $with = [])
    {
        $single = parent::single($id, array_merge($this->loadWith, ['company']));

        return $single;
    }


    public function makePreview($params = [])
    {
        $nds = Config::get('test.nds');
        if (count($params) == 0)
            $params = request()->all();

        Log::info('makePreview');
        Log::info($params);
        // График оплаты начиная с заданного числа, по умолчанию 10
        // $d_graf = $params['plan_graf'] > 0 ? $params['plan_graf'] : 10;

        $d_graf = 1; // всегда 1

        if (!isset($params['buyer_id'])) {
            $params['buyer_id'] = Auth::id();
        }

        $buyer = Buyer::find($params['buyer_id']);

        if (!$buyer->user->hasRole('buyer')) {
            $this->result['status'] = 'error';
            $this->message('danger', __('order.err_user_not_buyer'));
            return $this->result();
        }

        $config = Config::get('test.paycoin');

        // если у покупателя приобретена скидка
        if ($buyer->settings->paycoin_sale > 0) {
            $sale = $buyer->settings->paycoin_sale * $config['sale'];
        } else {
            $sale = 0;
        }


        //$order = $params['calculate']['orders'][$params['company_id']];
        foreach ($params['calculate']['orders'] as $companyId => $order) {
            $company = Company::find($companyId);
            $order['contract']['id'] = Contract::latest()->first() != null ? Contract::latest()->first()->id + 1 : 1;
            $order['contract']['date'] = date('d.m.Y');

            for ($i = 0, $iMax = count($order['contract']['payments']); $i < $iMax; $i++) {
                $order['contract']['payments'][$i]['date'] = date('d.m.Y', strtotime(Carbon::now()->addMonths($i + 1)->day($d_graf))); //date('d.m.Y', strtotime('+' . ($i + 1) . ' months'));
            }

            // если выбран период больше чем 12
            /*if($params['period']>12) {
                $month_discount = $company->settings['discount_12']; // берем тот же коэфф , что для 12 месяцев - 44%
                $month_markup = $company->settings['markup_12'] / 100; // берем тот же коэфф , что для 12 месяцев - %

            }else{
                $month_discount = $company->settings['discount_' . $params['period']];
                $month_markup = $company->settings['markup_' . $params['period']] / 100;
            }*/

            $month_discount = $company->settings['discount_' . $params['period']];
            $month_markup = $company->settings['markup_' . $params['period']] / 100;
            $month_discount = ($month_discount - $sale) / 100;

            //Корректируем стоимость с учетом кредита
            for ($i = 0, $iMax = count($order['products']); $i < $iMax; $i++) {

                //Скидка на товар
                /*if( isset($company->settings['discount_' . $params['period']]) && $company->settings['discount_' . $params['period']] != null )
                    $order['products'][$i]['price'] -= $order['products'][$i]['price'] * ($company->settings['discount_' . $params['period']] / 100); */

                //if($month_discount){
                $order['products'][$i]['price'] -= $order['products'][$i]['price'] * $month_discount;
                //}

                if ($order['price']['deposit'] > 0) {


                    //получаем процент одного продукта от общей стоимости
                    $clear_item_per = $order['products'][$i]['price'] / $order['price']['origin'];

                    // разбить депозит
                    $deposit_per = $order['price']['deposit'] * $clear_item_per;

                    // наша доля
                    $our_price = $order['products'][$i]['price'] - $deposit_per;

                    //  Наценка на нашу долю

                    /* if($company->settings['markup_' . $params['period']] != null)
                        $our_price +=  $our_price*($company->settings['markup_'.$params['period']]/100); */
                    //if($company->settings['markup_' . $params['period']] != null)

                    $our_price += $our_price * $month_markup; // ($company->settings['markup_'.$params['period']]/100);

                    //Сумма конкретного договора
                    $order['products'][$i]['price'] = $our_price + $deposit_per;

                    //НДС, если необходимо
                    if (!$company->settings['nds'] /*&& $order['products'][$i]['id'] == null*/) {
                        $order['products'][$i]['price'] = $order['products'][$i]['price'] * (1 + $nds);
                    }

                } else {
                    //НДС, если необходимо
                    if (!$company->settings['nds'] /*&& $order['products'][$i]['id'] == null*/)
                        $order['products'][$i]['price'] = $order['products'][$i]['price'] * (1 + $nds);

                    //Наценка на кредит
                    /*if ($company->settings['markup_' . $params['period']] != null)
                    $order['products'][$i]['price'] += $order['products'][$i]['price'] * ($company->settings['markup_' . $params['period']] / 100); */

                    $order['products'][$i]['price'] += $order['products'][$i]['price'] * $month_markup;

                }

                $order['products'][$i]['price'] = round($order['products'][$i]['price'], 2);
            }

            $data = [
                'order' => $order,
                'buyer' => $buyer,
                'nds' => Config::get('test.nds') * 100,
                'period' => $params['period']
            ];

            //Create PDF
            /*$folder = 'offerpreview/';
            $namePdf = md5(time()).'.pdf';
            $link = $folder.$namePdf;

            Log::info('offer_pdf name: ' . $namePdf);

            FileHelper::generateAndUploadPDF($link, 'cabinet.order.parts.offer_preview_pdf', $data);*/

            //Create SMS code
            $request = new Request();
            $request->merge(['phone' => $buyer->phone_with_out_plus]); // ???

            // $pdf_link = Storage::url('offerpreview/'.$namePdf); // тарый пдф, нужно изменить верству, как в новом, или отослать новый billing.order.parts.account_pdf
            // $pdf_link = 'client.test.uz';  // 27.10.2022 Nurlan переход c test на resusNasiya
            $pdf_link = 'resusnasiya.uz';

            $sms = $this->sendSmsCode($request, false,null,4);  // если false, возвращает только код и хаш, не шлет смс

            $msg = "Shartnomani tasdiqlash kodi - " . $sms['code']
                . ". Taklif (" . $pdf_link . ")."
                . " Shartnoma summasi: " . $order["price"]["total"] . " sum."
                . " Muddat: " . $params["period"] . " oy."
                . " Oylik tolov: " . $order["contract"]["payments"][0]["total"]
                . " sum. Tel: " . callCenterNumber(2);

            //Send SMS
            $this->result = $this->sendSmsCode($request, true, $msg,4);

            // сохранить линк на offer_preview
            $this->result['data'] = [
                // 'offer_preview' => $namePdf,
                'hashed' => $sms['hashed']
            ];

            break; //
        }
        Log::info($this->result);

        return $this->result();
    }

    /**
     * Order detail
     *
     * @param int $id
     * @return array
     */
    public function detail(int $id): array
    {

        $order = $this->single($id);
        $user = Auth::user();

        if ($order) {
            if ($user->can('detail', $order)) {

                foreach ($order->products as $product) {
                    if ($product->info) {
                        if (isset($product->info->images)) {
                            $preview = $product->info->images->first();
                            if ($preview) {
                                $previewPath = str_replace($preview->name, 'preview_' . $preview->name, $preview->path);
                                $product->preview = Storage::exists($previewPath) ? Storage::url($previewPath) : null;
                            }

                        }
                    }


                    if ($order->shipping_code)
                        $order->shipping_name = __('shipping/' . strtolower($order->shipping_code) . '.name');

                }

                $this->result['status'] = 'success';
                $this->result['data']['order'] = $order;
                $this->result['data']['nds'] = $this->config['nds'];
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
     * Order status change
     *
     * @param Request $request
     * @return array|false|string
     */
    public function changeStatus(Request $request)
    {
        $user = Auth::user();
        $order = Model::find($request->id)->load('products', 'contract', 'company', 'company.settings');

        if ($order) {
            if ($user->can('modify', $order) && in_array($request->status, $this->config['status'])) {

                //Update products count
                $operation = null;

                if ($request->status == 5) {
                    //Отмена договора
                    $cardController = new CardController();
                    $reqOrder = new Request();
                    $reqOrder->merge(['order_id' => $order->id]);
                    //$reqOrder->merge(['payment_id'=>76]);
                    $cardController->refund($reqOrder);
                    $operation = 'increment';
                    //При подтверждении договора меняем статус договора на "Отменен"
                    if ($order->contract) {
                        $order->contract->status = 5;
                        $order->contract->save();
                        $order->contract->canceled_at = date('Y-m-d H:i:s');
                        $order->contract->status = 5;

                        // создать минусовой договор с датой создания = дата отмены
                        $cancel_contract = new CancelContract();
                        $cancel_contract->contract_id = $order->contract->id;
                        $cancel_contract->user_id = $order->contract->user_id;
                        $cancel_contract->created_at = $order->contract->canceled_at;  // датой создания = дата отмены
                        $cancel_contract->canceled_at = $order->contract->canceled_at;  // датой создания = дата отмены
                        $cancel_contract->total = -1 * $order->contract->total;
                        $cancel_contract->balance = -1 * $order->contract->balance;
                        $cancel_contract->deposit = -1 * $order->contract->deposit;
                        $cancel_contract->save();
                    }
                } elseif ($request->status >= 3 && $request->status != 5) {
                    if ($order->contract) {
                        $order->contract->status = 1;
                        $order->contract->save();
                    }
                }

                //TODO: отмена транзакций и что делать с платежами??

                //Корректировка количества товара
                if ($operation && $order->company->settings->check_quantity == 1)
                    foreach ($order->products as $product)
                        if ($product->id)
                            CatalogProductController::quantity($product->id, $operation, $product->amount);

                $order->status = $request->status;
                $order->save();

                $notifyData = [
                    'order' => $order,
                    'buyer' => $order->buyer->user,
                ];
                NotificationHelper::orderStatusChanged($notifyData, app()->getLocale());

                $this->result['status'] = 'success';
                $this->message('success', __('billing/order.txt_status_changed'));
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
     * @param int $id
     * @return array|false|string
     * @throws Exception
     */
    public function delete(int $id)
    {
        $order = Model::find($id);


        if ($order) {
            $order->with('products', 'contract', 'contract.schedule');
            if ($order->products)
                $order->products->each->delete();

            if ($order->contract) {
                if ($order->contract->schedule)
                    $order->contract->schedule->each->delete();
                $order->contract->delete();
            }

            $order->delete();
            $this->result['status'] = 'success';

        }


        return $this->result();
    }

    public static function populateProducts($limit = 4)
    {
        $orderProducts = OrderProduct::all();
        $_products = [];
        if ($orderProducts) {
            foreach ($orderProducts as $product) {

                if (!isset($_products[$product->product_id]) && !is_null($product->product_id))
                    $_products[$product->product_id] = 0;

                if (!is_null($product->product_id))
                    $_products[$product->product_id]++;
            }
        }
        arsort($_products);
        $IDs = array_keys(array_slice($_products, 0, $limit, true));
        $productController = new CatalogProductController();
        return $productController->list(['limit' => $limit, 'id' => $IDs])['data'];
    }

    public function calculateBonus($params = [])
    {

        //Если параметры не переданы, берем их из запроса
        if (count($params) == 0) $params = request()->all();

        // Если есть ID магазина, идентифицируем магазин, чтобы в дальнейшем узнать коэффициент бонуса для продавцов этого магазина
        if (isset($params) && isset($params['partner_id']) && isset($params['seller_id'])) {
            $partner = User::find($params['partner_id']);
            $seller = Buyer::find($params['seller_id']);
        } else {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 404;
            $this->message('danger', __('Params aren\'t set'));
            return $this->result();
        }

        // Высчитываем чистую цену
        $calculation = $this->calculate($params, true);

        if ($calculation['status'] != 'error')
            $originPrice = $calculation['data']['price']['origin'];  // чистая цена

        if (isset($calculation) && isset($originPrice)) {

            $bonusAmount = SellerBonusesHelper::calculateBonus($partner->id, $originPrice);

            if ($seller) {
                $bonusAmount = round($bonusAmount * $seller->seller_bonus_percent / 100, 2);
            }

            $this->result['status'] = 'success';
            $this->result['response']['code'] = 200;
            $this->result['response']['data']['bonus_amount'] = $bonusAmount;
        }

        return $this->result();

    }

    protected static function getMarkup(Company $company, int $user_id, int $period): float
    {
        if ($user_id !== 0) {
            if ($period === 12 && self::isStaffMember($user_id)) {
                return Config::get('test.staff_markup'); // 20
            } else if ($company->reverse_calc == 1) {
                return (Config::get('test.rvs') - 1) * 100; // (1.42 - 1) * 100 = 42
            }
        }
        return $company->settings['markup_' . $period];
    }

    private static function isStaffMember(int $user_id): bool
    {
        return StaffPersonal::query()->where('status', StaffPersonal::STATUS_WORKS)
            ->whereIn('pinfl', BuyerPersonal::where('user_id', $user_id)->select('pinfl_hash'))
            ->exists();
    }
}
