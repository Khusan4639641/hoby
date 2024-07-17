<?php

namespace App\Services\API\V3\Partners;

use App\Helpers\FileHelper;
use App\Helpers\NdsStopgagHelper;
use App\Helpers\QRCodeHelper;
use App\Helpers\SellerBonusesHelper;
use App\Helpers\SmsHelper;
use App\Helpers\V3\OrderCreateHelper;
use App\Http\Controllers\Core\CatalogProductController;
use App\Http\Controllers\Core\ContractController;
use App\Http\Requests\AddOrderV3Request;
use App\Http\Requests\OrderCalculateV3Request;
use App\Http\Requests\OrderListV3Request;
use App\Models\Buyer;
use App\Models\BuyerPersonal;
use App\Models\Company;
use App\Models\Contract;
use App\Models\File;
use App\Models\GeneralCompany;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Partner;
use App\Models\PartnerSetting;
use App\Models\Role;
use App\Models\Saller;
use App\Models\StaffPersonal;
use App\Models\User;
use App\Services\API\V3\BaseService;
use App\Services\MFO\MFOOrderService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\Self_;
use Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class OrderService extends BaseService
{
    public static function validateOrderAdd(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'period' => 'required|integer',
            'products' => 'required|array',
            'products.*.unit_id' => 'required|integer',
            'products.*.category' => 'required|integer',
            'products.*.amount' => 'required|integer',
            'products.*.name' => 'required|string',
            'products.*.imei' => 'numeric',
            'products.*.price' => 'required|integer',
        ]);
        if ($validator->fails()) {
            self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function list(OrderListV3Request $request)
    {
        $params = $request->all();
        $status = [0, 5, 9];
        $per_page = 12;
        $user = Auth::user();
        $orders = Order::select('*')->with('products', 'products.info', 'products.info.images', 'buyer', 'contract', 'contract.schedule', 'contract.nextPayment', 'contract.activePayments', 'contract.clientAct', 'contract.cancelAct');
        if (!empty($params['status'])) {
            $status = $params['status'];
        }
        if (!empty($params['per_page'])) {
            $per_page = $params['per_page'];
        }
        $partner_settings = PartnerSetting::where('company_id', $user->company_id)->first();
        $manager_request = $partner_settings ? $partner_settings->manager_request : 0;
        $orders = $orders->selectRaw("$manager_request AS manager_request");

        //if exist searching key
        if (!empty($params['search'])) {
            $orders = self::search($orders, $params['search']);
        }
        $orders = self::searchByPhoneAndId($orders,$params);
        $orders = $orders->whereHas('contract', function ($query) use ($user, $params, $status) {
            if (!empty($params['cancellation_status'])) {
                $query->where('cancellation_status', 1);
            }
            $query->selectRaw("IF(DATE(created_at) > DATE(NOW()),0,1) AS isCancelBtnShow");
            $query->whereIn('status', $status);
        });
        $partner_id = $user->id;
        if (!empty($params['partner_id'])) {
            $partner = User::where('company_id', $user->company_id)->find($params['partner_id']);
            if ($partner) {
                $partner_id = $partner->id;
            }
        }
        if (!empty($params['cancellation_status']) && in_array($user->role_id,[ Role::SALES_MANAGER_ROLE_ID, Role::MEDIAPARK_SALES_MANAGER_ROLE_ID])) {
            $child_companies_list = Company::where('parent_id', $user->company_id)->pluck('id')->toArray();
            $orders->whereIn('company_id', $child_companies_list);
        } else {
            $orders = $orders->where('partner_id', $partner_id);
        }
        $result = $orders->orderBy('created_at', 'DESC')->paginate($per_page);
        $month_ago = Carbon::now()->subMonth()->format('Y-m-d');
        $result->getCollection()->transform(function ($item) use ($user, $month_ago, $result) {
            $item->totalDebt = 0;
            if ($item->contract) {
                //Если договору больше месяца << $item->isCancelBtnShow >> сделаем 1
                $item->isCancelBtnShow = strtotime($item->contract->created_at) > strtotime($month_ago) ? 0 : 1;

                foreach ($item->contract->debts as $debt) {
                    $item->totalDebt += $debt->total;
                }
            }

            foreach ($item->products as $product) {
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
            //Contract activation path for mfo contracts via webview
            $item->contract->webview_path = null;
            if($item->contract->general_company_id === GeneralCompany::MFO_COMPANY_ID) {
                $item->contract->webview_path = Config::get('test.webview_link') . '?contractId=' . $item->contract->id;
            }
            return $item;
        });

        return self::handleResponse($result);
    }

    public static function validateCalculate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'partner_id' => 'nullable|exists:users,id',
            'period' => 'required|integer',
            'products' => 'required|array',
            'products.*.amount' => 'required|integer',
            'products.*.price' => 'required|integer',
        ]);
        if ($validator->fails()) {
            self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function calculate(OrderCalculateV3Request $request, $flash = false)
    {
        $user = Auth::user();
        $params = $request->all();
        $partner = Partner::with('company')->find($user->id);
        $company = $partner->company;
        if (!$company) {
            return self::handleError([__('company.company_not_found')]);
        }
        if (isset($params['user_id'])) {
            $buyer = Buyer::with('settings')->find($params['user_id']);
        }
        $config = Config::get('test.paycoin');
        $sale = 0;
        // если у покупателя приобретена общая скидка
        if (isset($buyer->settings) && $buyer->settings->paycoin_sale > 0) {
            $sale = $buyer->settings->paycoin_sale * $config['sale'];
        }
        $nds = NdsStopgagHelper::getActualNds();
        //Подсчет суммы каждого договора
        //Заготовка договора
        $order = [
            'total' => 0,   //Конечная цена с учетом всех параметров и кредитов
            'shipping' => 0,   //Цена доставки
            'origin' => 0,   //Цена без кредита,
            'month' => 0,
            'partner' => 0,
            'deposit' => 0,
            'products' => [],
            'amount' => 0,       //Количество
            'contract' => []       //Если кредит
        ];

        //Формируем договор
        foreach ($params['products'] as &$product) {
            //Число товаров в договоре
            $order['amount'] += $product['amount'];
            $order['origin'] += $product['price'] * $product['amount'];
            $price = $product['price'] * $product['amount'];
            // если есть скидка на конкретный товар, сразу считаем со скидкой
            if (isset($product['price_discount']) && $product['price_discount'] > 0) {
                $price -= ($product['price_discount'] / 100) * $price;
                $order['origin'] -= $order['origin'] * $product['price_discount'] / 100;
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
            $order['total'] += $price;
            // цена конкретного товара УЖЕ со скидкой
            if (isset($company->reverse_calc) && $company->reverse_calc == 1) {
                $product['price'] = round(($product['price'] / Config::get('test.rvs') - ($product['price'] * $month_discount)) , 2);
            } else {
                $product['price'] = $product['price'] - ($product['price'] * $month_discount);
            }

        }
        // цена товара УЖЕ со скидкой (product_price)
        $order['products'] = $params['products'];
        //Сумма конкретного договора для продавца
        $order['partner'] += $order['origin'] - ($order['origin'] * $month_discount);  // со скидкой
        //Округляем до 2 знаков
        $order['origin'] = round($order['origin'], 2);
        $order['total'] = round($order['total'], 2);  // со скидкой
        $order['partner'] = round($order['partner'], 2);
        // если период 12 месяцев и покупатель наш сотрудник, то наценка особая
        $month_markup = self::getMarkup($company, $params['user_id'] ?? 0, $params['period']);
        // если клиент приобрел скидку, вычитаем из маржи
        $month_markup = ($month_markup - $sale) / 100;
        //Округляем до 2 знаков
        $order['origin'] = round($order['origin'], 2);
        $order['total'] = round($order['total'], 2);
        $order['partner'] = round($order['partner'], 2);
        // обратная калькуляция процентов (уже заложены в цене)
        $rvs = Config::get('test.rvs');
        if (isset($company->reverse_calc) && $company->reverse_calc == 1) {  // если это обратные проценты
            $order['origin'] = $order['origin'] / $rvs;
            $order['total'] = $order['total'] / $rvs;
            $order['partner'] = round($order['partner'] / $rvs); // вычитаем проценты из партнерской цены
        }
        // ДЕПОЗИТНЫЙ ВЗНОС
        // тут вычислим если хватает на депозитный взнос
        if (isset($buyer) && $order['origin'] >= $buyer->settings->balance) {
            $order['deposit'] = $order['origin'] - $buyer->settings->balance;
            // if there was a deposit, please minus it
            if ($order['deposit'] > 0) {
                // если без ндс
                $order['total'] -= $order['deposit'];
            }
        }
        //Конечная цена с учетом кредитной наценки
        if(isset($company->promotion) && $company->promotion == 1 && $params['period'] == 3) {
            $order['total'] = $order['origin'] - $order['deposit'];
        } else {
            $order['total'] += $order['total'] * $month_markup;
        }

        $order['total'] = round($order['total']);

        //Если партнер без НДС, накидываем сверху
        if (!$company->settings['nds']) {
            // if there was a deposit, please minus it
            if ($order['deposit'] > 0) {
                $order['total'] += $order['deposit'];  // прибавить депозит
                $order['total'] += $order['total'] * $nds;  // накинуть ндс
                $order['total'] -= $order['deposit']; // отнять депозит
            } else {
                $order['total'] += $order['total'] * $nds;
            }
        }
        //Ежемесячные платежи
        //если это обратные проценты, вычесть депозит

        $paymentMonthly = round($order['total'] / $params['period'], 2);

        $paymentMonthlyOrigin = round($order['origin'] / $params['period'], 2);
        $paymentMonthlyDeposit = round(($order['origin'] - $order['deposit']) / $params['period'], 2);
        $priceOrigin = $order['origin'];

        if ($order['deposit'] > 0) {
            $paymentMonthlyOrigin = $paymentMonthlyDeposit;
            $priceOrigin = $order['origin'] - $order['deposit'];
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
                    'total' => round($order['total'] - $paymentMonthly * ($params['period'] - 1), 2),
                    'origin' => round($priceOrigin - $paymentMonthlyOrigin * ($params['period'] - 1), 2)
                ];
            }
        }
        $order['contract']['payments'] = $payments;
        $order['month'] = $payments[0]['total'];

        return $flash ? $order : self::handleResponse($order);
    }

    public static function calculateBonus(AddOrderV3Request $request)
    {
        //create request for calculation
        $calculationRequest = new OrderCalculateV3Request();
        $calculationRequest->setMethod('POST');
        $calculationRequest->request->add($request->all());
        $calculation = self::calculate($calculationRequest, true);
        $partner_id = $request->seller_id;
        $seller_id = $request->has('seller_id') ? $request->get('seller_id') : 0;
        $price = $calculation['origin'];
        $bonus = 0;
        $seller = Saller::with('companyEmployer')->find($seller_id);
        if ($seller) {
            $company = $seller->companyEmployer;
            if ($company) {
                $coefficient = $company->seller_coefficient * Config::get('test.seller_coefficient');
                $bonus = (($price / NdsStopgagHelper::getActualNdsPlusOne()) * ($coefficient / 100)) * ($seller->seller_bonus_percent / 100);
                $bonus = round($bonus, 2);
            }
        }
        return self::handleResponse(['bonus_amount' => $bonus]);
    }

    public static function validateAddOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'partner_id' => 'nullable|exists:users,id',
            'period' => 'required|integer',
            'products' => 'required|array',
            'products.*.amount' => 'required|integer',
            'products.*.price' => 'required|integer',
        ]);
        if ($validator->fails()) {
            self::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public static function add(AddOrderV3Request $request)
    {
        $user = Auth::user();
        $params = $request->all();
        $company = Company::find($user->company_id);
        if (!$company) {
            return self::handleError([__('company.company_not_found')]);
        }
        if ($company->status == 0) {
            return self::handleError([__('app.user_is_blocked')]);
        }
        if ($company->general_company_id == 3) {
            return self::handleError([__('app.general_company_contract_not_allowed')]);
        }
        $buyer = Buyer::where(['id' => $params['user_id'], 'status' => 4])->first();
        if (!$buyer) {
            return self::handleError([__('billing/order.err_buyer_not_found')]);
        }
        if ($buyer->black_list) {
            return self::handleError([__('billing/order.err_black_list')]);
        }
        Log::info('add order');
        Log::info($params);
        $mfoService = new MFOOrderService;
        $mfoService->checkUserOverdueContracts($buyer);
        if (OrderCreateHelper::is_exists_mobile_categories($params['products'])) {
            $phones_count = BuyerService::getPhonesCount($params['user_id']);
            if (!OrderCreateHelper::is_available_buying_smartphones($params['products'], $phones_count)) {
                return self::handleError([__('billing/order.txt_phones_count')]);
            }
        }

        //create request for calculation
        $calculationRequest = new OrderCalculateV3Request();
        $calculationRequest->setMethod('POST');
        $calculationRequest->request->add($request->all());
        $calculation = self::calculate($calculationRequest, true);
        $origin = $calculation['origin'];  // чистая цена
        $buyer = Buyer::where(['id' => $params['user_id'], 'status' => 4])->first();
        $nds = NdsStopgagHelper::getActualNds();
        //если это обратные проценты, увеличить лимит на процент, который уже заложен в цене

        $creditLimit = $buyer->settings->balance + $buyer->settings->personal_account;

        // График оплаты начиная с заданного числа
        $d_graf = 1;
        //Проверка лимита покупателя - если срок на 24 месяца, сумма товаров должна быть больше 8 млн (задается в админке) - 23.12.2021
        if ($params['period'] == 24) {
            if ($calculation['origin'] < (int)$company->settings->limit_for_24) {
                return self::handleError([__('billing/order.err_limit')]);
            }
        }
        // сумма предоплаты по акции - 3 мес с предоплатой
        $promotion_percent = $company->settings->promotion_percent / 100;
        $promotion_prepayment = $calculation['origin'] * $promotion_percent;
        // если это акция, проверим хватает ли на ЛС денег для предоплаты
        if ($company->promotion == 1 && $params['period'] == 3 && $buyer->settings->personal_account < $promotion_prepayment) {
            return self::handleError([__('billing/order.err_promotion_percent')]);
        }
        // временная корректировка (9.3132257461548E-10 ) - поднимем лимит на 0.1
        if ($calculation['origin'] > $creditLimit + 0.1) {
            return self::handleError([__('billing/order.err_limit')]);
        }
        $config = Config::get('test.paycoin');
        // если у покупателя приобретена скидка
        if ($buyer->settings->paycoin_sale > 0) {
            $sale = $buyer->settings->paycoin_sale * $config['sale'];
        } else {
            $sale = 0;
        }
        //Сохраняем все договоры
        // если ввели телефон продавца, начислить ему бонусы для оплаты UPAY сервисов
        if (isset($params['seller_phone'])) {
            $seller_phone = correct_phone($params['seller_phone']);
            $seller = Buyer::where('phone', $seller_phone)->first();
        }
        //Create & save order
        $order = new Order();
        $order->partner_id = $user->id;
        $order->company_id = $company->id;
        $order->user_id = $params['user_id'];
        $order->total = $calculation['total'];
        $order->partner_total = $calculation['partner'];
        $order->credit = $order->partner_total;
        $order->debit = 0;
        $order->status = 0;
        $order->online = isset($params['online']) ? 1 : 0;  // все договоры от маркетплейса

        $order->save();
        //Сохраняем ID текущего договора в результирующий массив
        $result['data']['orders'][$order->company_id]['id'] = $order->id;

        $summ = 0;

        $rvs = Config::get('test.rvs');
        $month_markup = self::getMarkup($company, $buyer->id, $params['period']);
        $month_markup = ($month_markup - $sale) / 100;
        //Получение статуса партнера
        $company_settings = $company->settings;
        $is_trustworthy = $company_settings->is_trustworthy ?? 0;

        //Create & save order products
        foreach ($params['products'] as $productItem) {
            // чистая цена
            $price = $company->reverse_calc == 1 ? $productItem['price'] / $rvs : $productItem['price'];
            // если есть скидка на конкретный товар, сразу считаем со скидкой
            if (isset($productItem['price_discount']) && $productItem['price_discount'] > 0) {
                $price -= $price * $productItem['price_discount'] / 100;
            } else {
                // если товар без скидки, смотрим если есть другие скидки
                if ($params['period'] > 12) {
                    $month_discount = $company->settings['discount_12'] / 100; // берем тот же коэфф , что для 12 месяцев - 44%
                } else {
                    $month_discount = $company->settings['discount_' . $params['period']] / 100;
                }
            }

            $imei = !empty($productItem['imei']) ? ' IMEI: ' . $productItem['imei'] : '';

            $product = new OrderProduct();
            $product->order_id = $order->id;
            $product->name = str_replace(';', ',', $productItem['name'] . $imei);
            $product->label = $productItem['label'] ?? null;
            $product->price = $price; // $productItem['price'];
            // 14.07 imei
            $product->category_id = $productItem['category'] ?? null;
            $product->imei = $productItem['imei'] ?? null;

            //если с настройках компании есть флаг $is_trustworthy пытаемся записать, которые должны были отправиться с фронта  original_name и original_imei
            if ($is_trustworthy) {
                $product->original_imei = $productItem['original_imei'] ?? '';
                if (isset($productItem['original_name']) && isset($productItem['original_imei'])) {
                    $product->original_name = $productItem['original_name'] . " IMEI: " . $productItem['original_imei'];
                } else {
                    $product->original_name = $productItem['original_name'] ?? '';
                }
            }

            //Корректируем стоимость с учетом кредита
            //Скидка на товар
            $product->price_discount = $company->reverse_calc == 1 ? $productItem['price'] / $rvs : $productItem['price'];
            //}  // открыть если не так
            // депозит рассчитывается только если не было заложенных процентов в изначальную цену

            // if there was a deposit

            if ($calculation['deposit'] > 0) {
                //получаем процент одного продукта от общей стоимости
                $clear_item_per = $product->price / $order->partner_total;
                // разбить депозит
                $deposit_per = $calculation['deposit'] * $clear_item_per;
                // наша доля
                $our_price = $product->price - $deposit_per;

                //  Наценка на нашу долю
                $our_price += $our_price * $month_markup; //($company->settings['markup_'.$params['period']]/100);
                //Сумма конкретного договора
                $product->price = $our_price + $deposit_per;

                //НДС, если необходимо
                if (!$company->settings['nds'] && $product['id'] == null) {
                    $product->price = $product->price * (1 + $nds);
                }

            } else {

                //НДС, если необходимо
                if (!$company->settings['nds'] && $product['id'] == null)
                    $product->price = $product->price * (1 + $nds);

                $product->price += $product->price * $month_markup;
            }
            $summ += $product->price * $productItem['amount'];  // сумма всех товаров с наценкой
            $product->product_id = $productItem['id'] ?? null;
            $product->amount = $productItem['amount'];
            $product->weight = $productItem['weight'] ?? 0;
            $product->vendor_code = $productItem['vendor_code'] ?? '';
            $product->unit_id = $productItem['unit_id'];
            $product->save();

            // TODO Need to refactor  [whole CLASS!!!!] //EXTENDS FROM DEV-test-809/feature'
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
            if ($product->product_id && $company->settings->check_quantity == 1) {
                CatalogProductController::quantity($product->product_id, 'decrement', $productItem['amount']);
            }
        }


        // если есть разница между суммой товаров и конечной ценой, корректируем последний товар
        if ($calculation['total'] + $calculation['deposit'] - $summ != 0) {
            $product->price += abs($calculation['total'] + $calculation['deposit'] - $summ) / $product->amount;
            $product->save();
        }

        $contractController = new ContractController();

        //Create contract
        $paramsContract = [
            'user_id' => $order->user_id,
            'total' => $order->total,
            'period' => $params['period'],
            'deposit' => (isset($calculation['deposit'])) ? $calculation['deposit'] : 0,
            'partner_id' => $order->partner_id,
            'company_id' => $order->company_id,
            'order_id' => $order->id,
            'confirmation_code' => $params['sms_code'] ?? null,
            'offer_preview' => $params['offer_preview'] ?? null,
            'payments' => $calculation['contract']['payments'],
            'status' => (isset($params['cart']) ? 0 : 1),
            'd_graf' => $d_graf,
            'ox_system' => (isset($params['ox_system']) ? $params['ox_system'] : 0)  // все договоры от ox system **
        ];
        //TODO: Совместимость АПИ
        if (isset($params['created_at']))
            $paramsContract['created_at'] = $params['created_at'];

        $contract = $contractController->add($paramsContract, true);

        // если есть продавец магазина, регистрируем ему бонусы с продажи
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

        if ($contract['status'] == 'success') {
            //Calculate buyer balance
            $result['contract']['id'] = $contract['data']['id'];
            $buyer->settings->save();

        } else {
            Log::channel('contracts')->info('Ошибка при создании конктракта, не удалось создать контракт');
            Log::channel('contracts')->info($company->name . " - " . $contract['response']['errors']->all());
            return self::handleError([__('billing/order.err_cant_create_contract') . ' ' . $company->name]);
        }

        Log::channel('contracts')->info("Контракт " . $contract['data']['id'] . " создан  ");
        $result['data']['order_id'] = $order->id;
        $result['data']['contract_id'] = $contract['data']['id'];
        $result['message'] = __('billing/order.txt_created');

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

        $result = self::detail($order->id);
        $result['status_list'] = Config::get('test.order_status');

        if ($result['order']->contract) {
            $folderContact = 'contract/';
            $folder = $folderContact . $result['order']->contract->id;
            # Offer .PDF
            $namePdf = 'vendor_offer_' . $result['order']->contract->id . '.pdf';
            $link = $folder . '/' . $namePdf;
            ob_start();
            QRCodeHelper::url('https://' . $_SERVER['SERVER_NAME'] . '/storage/contract/' . $result['order']->contract->id . '/' . $namePdf);
            $imagedata = ob_get_contents();
            ob_end_clean();
            Log::info('QRcode create');
            $result['qrcode'] = '<img src="data:image/png;base64,' . base64_encode($imagedata) . '"/>';
            if (!FileHelper::exists($link)) {
                FileHelper::generateAndUploadPDF($link, 'billing.order.parts.offer_pdf', $result);
                Log::info('vendor_pdf create ' . $link);
            }
            $result['offer_pdf'] = '/storage/contract/' . $result['order']->contract->id . '/' . $namePdf;
            $result['offer_pdf'] = '/storage/contract/' . $result['order']->contract->id . '/' . $namePdf;
            # Account .PDF
            $namePdf = 'buyer_account_' . $result['order']->contract->id .Str::uuid(). '.pdf';
            $link = $folder . '/' . $namePdf;
            if (!FileHelper::exists($link)) {
                $fileInfo = pathinfo($link);
                FileHelper::generateAndUploadPDF($link, 'billing.order.parts.account_pdf', $result);
                Log::info('buyer_account_pdf create ' . $link);

                $file = new File;
                $file->element_id = $contract['data']['id'];
                $file->model = 'contract';
                $file->type = File::TYPE_CONTRACT_PDF;
                $file->name = $fileInfo['basename'];
                $file->path = $link;
                $file->language_code = $paramsContract['language_code'] ?? null;
                $file->user_id = $paramsContract['partner_id'];
                $file->doc_path = 1;
                $file->save();
            }
            $result['account_pdf'] = '/storage/contract/' . $result['order']->contract->id . '/' . $namePdf;
            $result['account_pdf'] = '/storage/contract/' . $result['order']->contract->id . '/' . $namePdf;
            $buyerInfo = Buyer::getInfo($buyer->id); //  buyer_id
            Log::channel('contracts')->info('buyerInfo');
            Log::channel('contracts')->info($buyerInfo);
            return self::handleResponse($result);
        }
        return self::handleResponse($result);
    }

    public static function detail(int $id)
    {
        $order = Order::with('company', 'products', 'products.info', 'products.info.images', 'buyer', 'contract', 'contract.schedule', 'contract.nextPayment', 'contract.activePayments', 'contract.clientAct')->find($id);
        if (!$order) {
            return [
                'status' => 'error',
                'message' => __('app.err_not_found'),
            ];
        }
        $user = Auth::user();
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
            if ($order->shipping_code) {
                $order->shipping_name = __('shipping/' . strtolower($order->shipping_code) . '.name');
            }
        }
        $result['status'] = 'success';
        $result['order'] = $order;
        $result['nds'] = Config::get('test.nds');
        return $result;
    }

    public static function getMarkup(Company $company, int $user_id, int $period): float
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

    private static function search(Builder $records, int $searchingValue)
    {
        return $records->whereHas('contract', function ($query) use ($searchingValue) {
            $query->where('id', $searchingValue);
        })->orWhereHas('buyer', function ($query) use ($searchingValue) {
            $query->where('phone', 'like', "$searchingValue%");
        });
    }

    private static function searchByPhoneAndId(Builder $records, array $params)
    {
        if(!empty($params['search_id'])){
            $records = $records->whereHas('contract', function ($query) use ($params) {
                $query->where('id', $params['search_id']);
            });
        }
        if(!empty($params['search_phone'])) {
            $records = $records->whereHas('buyer', function ($query) use ($params) {
                $query->where('phone', 'like', '%'.$params['search_phone'].'%');
            });
        }
        return $records;
    }
}
