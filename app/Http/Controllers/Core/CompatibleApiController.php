<?php

namespace App\Http\Controllers\Core;

use App\Helpers\EncryptHelper;
use App\Helpers\FileHelper;
use App\Helpers\NdsStopgagHelper;
use App\Helpers\QRCodeHelper;
use App\Helpers\SellerBonusesHelper;
use App\Helpers\SmsHelper;
use App\Http\Controllers\Core\Auth\LoginController;
use App\Http\Requests\Core\CompatibleApiController\CheckContractSmsCodeRequest;
use App\Http\Requests\Core\CompatibleApiController\SendContractSmsCodeRequest;
use App\Models\Buyer;
use App\Models\CancelContract;
use App\Models\CatalogCategory;
use App\Models\Company;
use App\Models\GeneralCompany;
use App\Models\Order;
use App\Models\Contract;
use App\Models\OrderProduct;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\SellerBonus;
use App\Models\User;
use App\Services\API\V3\BaseService;
use App\Services\API\V3\ContractVerifyService;
use App\Services\MFO\MFOPaymentService;
use App\Traits\UzTaxTrait;
use PDF;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CompatibleApiController extends CoreController
{
    //
    /**
     * @OA\Schema(
     *     schema="products",
     *     type="object",
     *     title="Products",
     *     properties={
     *         @OA\Property(property="amount", type="integer", example="1"),
     *         @OA\Property(property="name", type="string", example="SmartPhone SP12"),
     *         @OA\Property(property="price", type="integer", example="234555"),
     *     }
     * )
     */
    /**
     * @OA\Post(
     *      path="/buyers/verification",
     *      operationId="compatible-verification",
     *      tags={"Buyer compatible old test API"},
     *      summary="Check verification user",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="Buyer ID",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="phone",
     *          description="Buyer phone",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *       response=201,
     *       description="Success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    public function verification(Request $request)
    {
        //$request->query->remove('api_token');
        $controllerBuyer = new BuyerController();
        $params = [
            'status' => null
        ];
        if ($request->has('id')) $params['id'] = $request->id;
        if ($request->has('phone')) $params['phone'] = $request->phone;

        if ($request->has('id') || $request->has('phone')) {
            $objectBuyer = $controllerBuyer->list($params);
            if (sizeof($objectBuyer['data']) > 0) {

                $buyer = $objectBuyer['data'][0];
                $fio = $buyer->fio;

                $message = [
                    0 => 'страница регистрации',
                    1 => 'добавить карту',
                    2 => 'страница ожидания',
                    4 => 'верифицирован',
                    5 => 'лицевая сторона паспорта',
                    8 => 'пользователь заблокирован',
                    10 => 'селфи с паспортом',
                    11 => 'страница паспорта с пропиской',
                    12 => 'добавить доверителя',
                ];

                if ($buyer) {
                    //если вендор сам платит за клиента, проверим, его ли это вендор (не разрешено покупать у других вендоров)
                    if ($buyer->vip) {
                        $user = Auth::user();
                        $partner = Partner::find($user->id);
                        if (isset($partner->company)) {
                            if ($partner->company->vip == 1) {
                                if ($user->id != $buyer->created_by) {
                                    $response['result'] = 0;
                                    $response['message'] = __('billing/order.err_vip_list');
                                    return $response;
                                }
                            }
                        }

                    }

                    // проверка на черный список
                    if ($buyer->black_list) {
                        $response['result'] = 0;
                        $response['message'] = __('billing/order.err_black_list');
                        return $response;
                    }

                    $response['result'] = $buyer->status;
                    $response['message'] = $message[$buyer->status]; //__('api.buyer_verified', ['fio' => $fio]);
                    $response['buyer_id'] = $buyer->id;

                    if ($buyer->status == 4) {
                        $balance = $buyer->settings->balance == 0 ? 0 : $buyer->settings->balance + $buyer->settings->personal_account;
                        $balance = $balance < 0 ? 0 : $balance;
                        $response['available_balance'] = number_format($balance, 2, ".", "");
                    }
                }

                /*} else {
                    $response['result'] = 2;
                    $response['message'] = __('api.buyer_not_verified', ['fio' => $fio]);
                }*/
            } else {
                $response['result'] = 0;
                $response['message'] = __('api.buyer_not_found');
            }
        } else {
            $response['result'] = 0;
            $response['message'] = __('api.incorrect_parameters');
        }

        return $response;
    }

    /**
     * @OA\Post(
     *      path="/buyers/create-basket",
     *      operationId="compatible-create-basket",
     *      tags={"Buyer compatible old test API"},
     *      summary="Add pre-order for user and non-register user",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *      @OA\Parameter(
     *          name="phone",
     *          description="Buyer phone",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="limit",
     *          description="Credit limit",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              enum={3,6,9},
     *              example="6"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="vendor_id",
     *          description="Vendor ID",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\RequestBody(
     *       required=true,
     *       description="Products body",
     *       @OA\JsonContent(
     *           @OA\Property(
     *               property="products",
     *               @OA\Items(ref="#/components/schemas/products"),
     *           )
     *       )
     *     ),
     *
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *       response=201,
     *       description="Success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    public function CreateBasket(Request $request)
    {
        $user = Auth::user();
        $bErr = false;
        $result = [
            'status' => 0,
            'error' => null
        ];

        if (!$request->has('vendor_id')) {
            $result['error'] = __('api.credit_date');
            $bErr = true;
        } elseif (!$request->has('limit')) {
            $result['error'] = __('api.credit_limit');
            $bErr = true;
        } elseif ($request->limit != 3 && $request->limit != 6 && $request->limit != 9 && $request->limit != 12) {
            $result['error'] = __('api.credit_limit_max_limit', ['credit_limit' => $request->credit_limit]);
            $bErr = true;
        } elseif (!$request->has('products') || sizeof($request->products) == 0) {
            $result['error'] = __('api.credit_empty_products');
            $bErr = true;
        }

        if (!$bErr) {
            $login = new LoginController();
            $request->merge(['role' => 'buyer']);
            $validate = $login->validateForm($request);
            if ($validate['status'] == 'success' && $validate['response']['code'] == 404) {
                $user = $login->registerAndAuth($request);
                $request->merge(['user_id' => $user['data']['id']]);
            } else {
                $buyer = Buyer::where('phone', $request->phone)->first();
                $request->merge(['user_id' => $buyer->id]);
            }
            $data = $request->all();
            $data['credit_limit'] = $data['limit'];
            $data['id'] = $data['vendor_id'];
            unset($data['role']);
            unset($data['limit']);
            unset($data['vendor_id']);
            $data['credit_date'] = Carbon::now()->addMonth($data['credit_limit'])->format('d-m-Y');
            $credit = new Request();
            $credit->merge($data);
            $arOrder = '';
            if (!$bErr)
                $arOrder = $this->addCredit($credit);
            if (isset($arOrder['user'])) {
                $result['status'] = 1;
                $result['client_id'] = $request->user_id;
            } else {
                $result['error'] = $arOrder['error'];//__('api.basket_error_create_basket');
            }
        }
        return $result;
    }

    /**
     * @OA\Post(
     *      path="/buyers/add-credit",
     *      operationId="compatible-add-credit",
     *      tags={"Buyer compatible old test API"},
     *      summary="Add credit contract and order items",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *      @OA\Parameter(
     *          name="credit_date",
     *          description="Credit date",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              example="12.12.2021"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="credit_limit",
     *          description="Credit limit",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              enum={3,6,9},
     *              example="6"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="user_id",
     *          description="Buyer ID",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="phone",
     *          description="Partner phone",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\RequestBody(
     *       required=true,
     *       description="Products body",
     *       @OA\JsonContent(
     *           @OA\Property(
     *               property="products",
     *               @OA\Items(ref="#/components/schemas/products"),
     *           )
     *       )
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *       response=201,
     *       description="Success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    public function addCredit(Request $request)
    {
        $user = Auth::user();
        $bErr = false;
        $result = [
            'result' => [
                'status' => 0
            ]
        ];

        if ($request->has('buyer_phone')) {
            //$buyer = Buyer::find($request->user_id);
            $buyer = Buyer::where('phone', $request->buyer_phone)->where('status', 4)->first();
            if (!$buyer) {
                $result['error'] = 'buyer not found or not verified';
                return $result;
            }

        } else {
            $result['error'] = 'empty user';
            return $result;
        }


        if ($user->hasRole('employee')) {
            if ($request->has('partner_id')) {
                $partner = Partner::find($request->partner_id);
                if ($partner != null) {
                    $company_id = $partner->company_id;
                } else {
                    $result['error'] = __('api.credit_partner_not_found');
                    $bErr = true;
                }
            } else {
                $result['error'] = __('api.credit_empty_partner');
                $bErr = true;
            }
        } elseif ($user->hasRole('partner')) {
            $partner = Partner::find($user->id);
            $company_id = $user->company_id;
        }

        if(!isset($partner))
        {
            $result['error'] = 'buyer not found or not verified';
            return $result;
        }

        $plans = [3,6,9,12,24];

        if (!$request->has('limit')) {
            $result['message'] = __('api.credit_limit');
            $bErr = true;
            //} elseif (/*$request->limit != 1 && */ $request->limit != 3 && $request->limit != 6 && $request->limit != 9 && $request->limit != 12 && $request->limit != 24) {
        } elseif (!in_array($request->limit, $plans)) {
            $result['message'] = __('api.credit_limit_max_limit', ['credit_limit' => $request->limit]);
            $bErr = true;
        } elseif (!$request->has('products') || sizeof($request->products) == 0) {
            $result['message'] = __('api.credit_empty_products');
            $bErr = true;
        }

        // набираем включенные лимиты из админки, если не включены - берем все из конфига
        $plans_get = [];
        if ($partner->company->settings->limit_3 == 1) {
            $plans_get[] = 3;
        }
        if ($partner->company->settings->limit_6 == 1) {
            $plans_get[] = 6;
        }
        if ($partner->company->settings->limit_9 == 1) {
            $plans_get[] = 9;
        }
        if ($partner->company->settings->limit_12 == 1) {
            $plans_get[] = 12;
        }
        $plans_get = $plans_get ? implode(",", $plans_get) : 0;

        // если ни один не задан период, тогда не продаем ничего
        $limit = 'limit_' . $request->limit;
        if ($partner->settings->$limit == 0) {
            $result['message'] = __('api.credit_limits', ['plans_get' => $plans_get]);
            $bErr = true;
        }

        $balance = $buyer->settings->balance == 0 ? 0 : $buyer->settings->balance + $buyer->settings->personal_account;
        $balance = $balance < 0 ? 0 : $balance;

        $old_to_new_categories = [
            0 => 12,
            1 => 1330,
            2 => 1330,
            3 => 1357,
            4 => 1304,
            5 => 1245,
            6 => 1260,
            7 => 1304,
            8 => 1398,
            9 => 1357,
            10 => 1513,
            11 => 1947,
        ];
        $products = $request->products;

        // проверка хватает ли лимита у клиента
        $clear_price = 0;
        foreach ($products as $k => $product) {
            if(array_key_exists('category', $product)) {
                $old_category_id = $product['category'];
                if(array_key_exists($old_category_id, $old_to_new_categories)) {
                    $products[$k]['category'] = $old_to_new_categories[$old_category_id];
                    $request->merge(['products' => $products]);
                }
            }

            $clear_price += $product['price'] * $product['amount'];
        }
        if ($balance < $clear_price) {
            $result['message'] = 'Не достаточно баланса(' . $balance . ' сум) для покупок.';
            $bErr = true;
        }
        if ($request->limit == 24 && $clear_price < (int)$partner->settings->limit_for_24) {
            $result['message'] = __('api.credit_limit_for_24', ['limit_for_24' => (int)$partner->settings->limit_for_24]);
            $bErr = true;
        }

        // проверка на задолженность
        $debts = 0;
        foreach ($buyer->contracts as $contract) {
            if (in_array($contract->status, [1, 3, 4])) {
                foreach ($contract->schedule as $schedule) {
                    $payment_date = strtotime($schedule->payment_date);
                    $now = strtotime(Carbon::now()->format('Y-m-d 23:59:59'));
                    if ($schedule->status == 0 && $payment_date <= $now) {
                        $debts += $schedule->balance;
                    }
                }
            }
        }
        if ($debts > 0) {
            $result['message'] = 'У клиента имеется задолженность';
            $bErr = true;
        }

        // если вендор сам платит за клиента, проверим его ли это клиент
        if ($partner->company->vip == 1) {
            if ($partner->id != $buyer->created_by) {
                $result['message'] = __('billing/order.err_vip_list');
                $bErr = true;
            }
        }


        // число для графика оплаты, если не задано, отправляем 0 - по умолчанию
        /*$plan_graf = 0;
        if (isset($request->payment_day)) {
            $plan_graf = $request->payment_day;
            if ($request->payment_day > 0 && $request->payment_day < 5) {
                $plan_graf = 5;
            } elseif ($request->payment_day > 15) {
                $plan_graf = 15;
            }
        }*/

        $plan_graf = 1;  // всегда 1

        if (!$bErr) {
            $order = new OrderController();

            $params = [
                'type' => 'credit',
                'period' => $request->limit,
                'plan_graf' => $plan_graf,
                'products' => [$company_id => $request->products],
                'user_id' => $buyer->id,
                'sms_code' => '',
                'partner_id' => $partner->id,
            ];
            // все договора от маркетплейса - необязательный параметр
            if (isset($request->online)) $params['online'] = $request->online;

            // все договора от OKS system - необязательный параметр
            if (isset($request->ox_system)) $params['ox_system'] = $request->ox_system;

            $arOrder = $order->add($params);

            if ($arOrder['status'] == 'success') {

                $contract = Contract::where('order_id', $arOrder['data']['order_id'])->first();
                $collection = collect($contract->schedule);

                $paymentGraph = $collection->map(function ($item) {
                    return array(
                        "payment_date" => Carbon::parse($item['payment_date'])->format('d.m.Y'),
                        "payment_total" => number_format($item['total'], 2, ".", ""),
                    );
                });
                // если есть депозит, добавляем как первый платеж
                if ($contract->deposit > 0) {
                    $paymentGraph->prepend([
                        "payment_date" => $contract->created_at,
                        "initial_paid" => number_format($contract->deposit, 2, ".", ""),
                    ]);
                }


                $vendNdsPrice = [];
                $vendNds = [];

                foreach ($arOrder['data']['orders'][$company_id]['products'] as $k => $v) {
                    $vnp = number_format(ceil($v['price'] * $v['amount'] / NdsStopgagHelper::getActualNdsPlusOne()), 2, ".", "");
                    $vendNdsPrice[] = number_format($vnp, 2, ".", "");
                    $vendNds[] = number_format($v['price'] * $v['amount'] - $vnp, 2, ".", "");
                }

                $result = [
                    "status" => 1,
                    "test_client" => [
                        "fio" => $buyer->fio,
                        "phone" => $buyer->phone,
                        "contract_id" => $arOrder['data']['orders'][$company_id]['id'],
                        "client_order_id" => $arOrder['data']['orders'][$company_id]['id'],
                        "client_contract_id" => $arOrder['data']['contract']['id'],
                        "created_at" => Carbon::parse(time())->format('d.m.Y'),
                        "price_month" => number_format($arOrder['data']['price']['month'], 2, ".", ""),
                        "total" => number_format($arOrder['data']['price']['total'], 2, ".", ""),
                        "available_balance" => number_format($buyer->settings->balance + $buyer->settings->personal_account, 2, ".", "")
                    ],
                    "cart" => $arOrder['data']['orders'][$company_id]['products'],
                    "payment_schedule" => $paymentGraph,
                    "without_nds_product_price" => $vendNdsPrice,
                    "nds" => $vendNds,
                    "act_pdf" => $arOrder['data']['account_pdf'] ?? null,
                    "client_act_pdf" => $arOrder['data']['full_path_account_pdf'] ?? null
                ];

                $paramPreview = [
                    'period' => $request->limit,
                    'plan_graf' => $plan_graf,
                    'calculate' => $order->calculate($params)['data'],
                    'company_id' => $company_id,
                    'buyer_id' => $buyer->id,
                    'flag_send' => 'send'
                ];

                $sms = $order->makePreview($paramPreview);
                //$contract->offer_preview = $sms['data']['offer_preview'];
                $contract->save();

                //$result['offer_preview'] = $sms['data']['offer_preview'];
                Redis::set($request->buyer_phone . '-' . $arOrder['data']['orders'][$company_id]['id'], $sms['data']['hashed']);


            } else {
                $result['error'] = $arOrder['response']['message'][0]['text'];
            }
        }
        return $result;
    }

    /**
     * @OA\Post(
     *      path="/buyers/check-user-sms",
     *      operationId="compatible-check-user-sms",
     *      tags={"Buyer compatible old test API"},
     *      summary="Check user sms",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *      @OA\Parameter(
     *          name="user_id",
     *          description="Buyer ID",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="credit_id",
     *          description="Credit identificator from API function add-credit",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="code",
     *          description="SMS code from API function add-credit",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *       response=201,
     *       description="Success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     *
     * ДЛЯ АПИ СОЗДАНИЯ ДОГОВОРА С ПОСТОРОННИХ САЙТОВ
     */
    public function CheckUserSms(Request $request)
    {

        $bErr = false;
        $result = [
            'result' => [
                'status' => 0
            ],
            'error' => null
        ];

        if (!$request->has('phone')) {
            $result['error'] = __('api.check_sms_user_not_found');
            $bErr = true;
        } elseif (!$request->has('contract_id')) {
            $result['error'] = __('api.check_sms_credit_id_not_found');
            $bErr = true;
        } elseif (!$request->has('code')) {
            $result['error'] = __('api.check_sms_code_not_found');
            $bErr = true;
        }

        $buyer = Buyer::where("phone", $request->phone)->first();
        if ($buyer == null) {
            $result['error'] = __('api.buyer_not_found');
            $bErr = true;
        }

        $order = Order::where('id', $request->contract_id)->with('partnerSettings')->first();  // пчм order? по апи
        if (!$order) {
            $result['error'] = __('api.contract_not_found');
            $bErr = true;
            return $result;
        }

        $created_at = Carbon::parse($order->contract->created_at);

        // Если с момента создания контракта прошло больше 1 часа
        if ( $created_at->diffInMinutes() > 60) {
            $bErr = true;
            $result['error'] = 'contract_out_of_date';
            return $result;
        }

        if (!$bErr) {
            $hash = Redis::get($request->phone . '-' . $request->contract_id);

            $checkSms = new Request();
            $checkSms->merge([
                'code' => $request->code,
                'hashedCode' => $hash,
                'phone' => $request->phone
            ]);

            $resultCheck = $this->checkSmsCode($checkSms);

            if ($resultCheck['status'] == 'success') {

                $order->contract->confirmation_code = $request->code;
                if (isset($order->partnerSettings) && $order->partnerSettings->contract_confirm == 1) {  // если нужно подтверждение вендора
                    $order->contract->status = 2;  //  на подтверждении
                } else {
                    $order->contract->status = 1;  // подтвержден
//                    SellerBonusesHelper::activateBonusByContract($request->contract_id);
                }
                // дата подтверждения договора клиентом/покупателем
                $order->contract->confirmed_at = Carbon::now()->format('Y-m-d H:i:s');

                $balance = $buyer->settings->balance - ($order->credit - $order->contract->deposit); // проверка хватает ли лимита
                if ($balance < 0) {
                    $result['error'] = 'Не достаточно баланса (' . $buyer->settings->balance . ' сум) для покупок.';
                    return $result;
                }
                $buyer->settings->balance = $balance;
                $buyer->settings->save();

                $order->contract->save();
                $order->status = 9;
                $order->save();

                Redis::del($request->phone . '-' . $request->contract_id);
                Redis::del($buyer->phone);


                if ($order->contract->status == 1) {  // если НЕ нужно подтверждение вендора и контракт подтвержден клиентом
                    $buyer->settings->personal_account -= $order->contract->deposit; // снять после подтверждения смс кода
                    $buyer->settings->save();

                    // IF DEPOSIT
                    if ($order->contract->deposit > 0) {

                        // записать как транзакцию в payments
                        $payment = new Payment;
                        $payment->schedule_id = $order->contract->schedule[0]->id;
                        $payment->type = 'auto';
                        $payment->order_id = $order->id;
                        $payment->contract_id = $order->contract->id;
                        $payment->amount = $order->contract->deposit;
                        $payment->user_id = $buyer->id;
                        $payment->payment_system = 'DEPOSIT';
                        $payment->status = 1;
                        $payment->save();
                    }
                    ContractVerifyService::instantVerification($order->contract);
                }


                $result['result']['status'] = 1;
                $result['result']['contract_id'] = $order->id;  // вообще непонятно, почему order
                $result['result']['message'] = __('api.check_sms_contract_success');
            } else {
                $result['error'] = __('api.check_sms_code_not_found');
            }
        }
        return $result;
    }

    /**
     * ДЛЯ АПИ СОЗДАНИЯ ДОГОВОРА С ПОСТОРОННИХ САЙТОВ - ПОДТВЕРЖДЕНИЕ ВЕНДОРОМ
     *
     * @param Request $request
     * @return mixed
     */
    public function PartnerConfirm(Request $request)
    {
        $user = Auth::user();

        $bErr = false;
        $result = [
            'result' => [
                'status' => 0
            ],
            'error' => null
        ];


        if ($user->hasRole('employee')) {
            if ($request->has('partner_id')) {
                $partner = Partner::find($request->partner_id);
                if ($partner != null) {
                    $company_id = $partner->company_id;
                } else {
                    $result['error'] = __('api.credit_partner_not_found');
                    $bErr = true;
                }
            } else {
                $result['error'] = __('api.credit_empty_partner');
                $bErr = true;
            }
        } elseif ($user->hasRole('partner')) {
            $partner = Partner::find($user->id);
            $company_id = $user->company_id;
        }

        if (!$request->has('contract_id')) {  // тут присылают order_id
            $result['error'] = __('api.check_sms_credit_id_not_found');
            $bErr = true;
        }


        if (!$contract = Contract::where(['order_id' => $request->contract_id, 'status' => 2, 'company_id' => $company_id])->with('order', 'schedule')->first()) { // пчм order? по апи
            $result['error'] = 'contract not found';
            $bErr = true;
        }


        if ($contract && !$buyer = Buyer::where('id', $contract->user_id)->first()) {
            $result['error'] = 'buyer not found';
            $bErr = true;
        }

        if (!$bErr) {

            $contract->status = 1;  // подтверждает договор

            // Отключено задачей test-964:
//            $contract->confirmed_at = date('Y-m-d');  // подтверждает договор
            $now = Carbon::now()->format('Y-m-d H:i:s');

            $contract->created_at = $now;  // дата подтверждения договора мерчантом/вендором
            $contract->save();
            $contract->order->created_at = $now;  // Order. дата подтверждения мерчантом/вендором
            $contract->order->save();

            ContractVerifyService::instantVerification($contract);

            Log::channel('contracts')->info('Partner confirm - ' . $contract->id . ' contract confirmed by company ' . $partner->company->name . ' ID ' . $company_id);

            //$buyer->settings->balance -= $contract->order->credit - $contract->deposit; //снять после подтверждения вендором
            $buyer->settings->personal_account -= $contract->deposit; // снять после подтверждения вендором
            $buyer->settings->save();

            // IF DEPOSIT
            if ($contract->deposit > 0) {

                // записать как транзакцию в payments
                $payment = new Payment;
                $payment->schedule_id = $contract->schedule[0]->id;
                $payment->type = 'auto';
                $payment->order_id = $contract->order->id;
                $payment->contract_id = $contract->id;
                $payment->amount = $contract->deposit;
                $payment->user_id = $buyer->id;
                $payment->payment_system = 'DEPOSIT';
                $payment->status = 1;
                $payment->save();
            }

            $now_day = Carbon::now()->format('d');
            $first_month_day = $now_day > 20 ? 15 : 1; // если договор оформлен >= 21, то первый месяц оплаты 15, остальные 1  - update 20.08.2021

            // заменить число в графике платежей
            $i = 0;
            foreach ($contract->schedule as $schedule) {

                if ($i == 0) {
                    $day = $first_month_day;
                } else {
                    $day = 1;
                }

                if ($now_day > 27) {  // после 27 числа каждого месяца корректировка
                    // $schedule->payment_date = strtotime(Carbon::now()->addDay(25)->day($day));

                    if ($i == 0) {
                        $schedule->payment_date = strtotime(Carbon::now()->addDay(25)->day($day));
                    } else {
                        $schedule->payment_date = strtotime(Carbon::now()->addDay(25)->addMonths($i)->day($day));
                    }

                } else {
                    $schedule->payment_date = strtotime(Carbon::now()->addMonths($i + 1)->day($day));
                }

                $schedule->save();
                $i++;

            }
            $result['status'] = 1;
            $result['result']['status'] = 1;
            $result['message'] = 'the contract was successfully confirmed';

            $products = $contract->order->products;
            foreach ($products as $product) {
                $prod[] = [
                    "price" => $product->price_discount,
                    "amount" => $product->amount,
                    "name" => $product->name,
                ];
            }
            foreach ($contract->schedule as $schedule) {
                $sched[] = [
                    "total" => $schedule->total,
                    "origin" => $schedule->price,
                    "date" => $schedule->payment_date,
                ];
            }
            // удаляем старый пдф
            // генерируем новый пдф
            $order = [
                "price" => [
                    "total" => $contract->order->total,
                    "origin" => $contract->order->credit,
                    "partner" => $contract->order->partner_total,
                    "deposit" => $contract->deposit,
                ],
                "products" => $prod,
                "contract" => [
                    "payments" => $sched,
                    "id" => $contract->id,
                    "date" => $contract->created_at,
                ]

            ];

            $data = [
                'order' => $order,
                'buyer' => $buyer,
                'nds' => Config::get('test.nds') * 100,
                'period' => $contract->period
            ];

            //Create PDF
            $folder = 'offerpreview/';
            $namePdf = md5(time()) . '.pdf';
            $link = $folder . $namePdf;

            Log::info('offer_pdf name: ' . $namePdf);
            FileHelper::generateAndUploadPDF($link, 'cabinet.order.parts.offer_preview_pdf', $data);
            $contract->offer_preview = $namePdf;
            $contract->save();

            //////////////////////////////////////////
            $folderContact = 'contract/';
            $folder = $folderContact . $contract->id;

            $order = Order::where('id', $contract->order->id)->with('products', 'contract')->first();

            $new_data = [];
            $new_data['price'] = $data['order']['price'];
            $new_data['orders'][$contract->partner_id] = $data['order'];
            $new_data['contract'] = $contract;
            $new_data['order'] = $order;

            # Account .PDF
            $namePdf = 'buyer_account_' . $contract->id . '.pdf';
            $link = $folder . '/' . $namePdf;

            FileHelper::generateAndUploadPDF($link, 'billing.order.parts.account_pdf', $new_data);
            Log::info('buyer_account_pdf create ' . $link);

            $result['data']['act'] = '/storage/contract/' . $contract->id . '/' . $namePdf;

            Log::channel('contracts')->info('act ' . $result['data']['act']);
        }
        return $result;


    }

    /**
     * Send sms code contract - back vendor page
     *
     * @param Request $request
     * @return mixed
     */
    public function SendContractSmsCode(SendContractSmsCodeRequest $request)
    {
        $contract = Contract::find($request->contract_id);
        $created_at = Carbon::parse($contract->created_at);

        // Если с момента создания контракта прошло больше 1 часа
        if ( $created_at->diffInMinutes() > 60) {
            $this->result['status'] = 'error';
            $this->result['response']['code'] = 406;
            $this->result['response']['message'] = 'contract_out_of_date';
            return $this->result;
        }

        $link = config('test.test_link') . $contract->id;


        if (!$request->flag) {
            $msg = 'resusNasiya / :code - Shartnomani tasdiqlash kodi. Xaridingiz uchun rahmat! Tel: ' . callCenterNumber(2);
        } else {
            $msg = "resusNasiya / Shartnomani tasdiqlash uchun quyidagi havola orqali o'ting " . $link;
        }

        $result = $this->sendSmsCode($request, true, $msg);

        if ($result['status'] === 'success') {
            Log::channel('contracts')->info("Отправка смс кода клиенту о создании контракта");
            Log::channel('contracts')->info("Sms code: " . $request->phone . ': ' . $msg);
        } else {
            Log::channel('contracts')->info("НЕ отправлен смс код клиенту о создании контракта");
        }

        $this->result['status'] = 'success';
        $this->result['response']['code'] = 200;
        $this->result['data']['contract_id'] = $contract->id;

        return $this->result;
    }

    /**
     * Verify sms code contract - front page
     *
     * @param Request $request
     * @return mixed
     */
    public function CheckContractSmsCode(CheckContractSmsCodeRequest $request)
    {
        $buyer = Buyer::where("phone", $request->phone)->first();

        $contract = Contract::where(['id' => $request->contract_id, 'user_id' => $buyer->id])->with('orderProducts.category.language')->first();

        $company = Company::find($contract->company_id);

        // проверка - не выйдет ли клиент за лимит если подтвердит договор
        $avaliable_balance = $buyer->settings->balance + $buyer->settings->personal_account;
        $avaliable_balance -= $contract->order->credit - $contract->deposit;

        if ($avaliable_balance < 0) {
            $this->result['response']['message'] = 'limit_error';
            return $this->result;
        }

        $encSms = $this->checkSmsCode($request);

        if ($encSms['status'] == 'success') {
            $this->result['status'] = 'success';

            $contract->confirmation_code = $request->code;
            $contract->status = 1;
            $contract->save();
            $contract->order->status = 9;
            $contract->order->save();

            // если это трехмесячная акция
            if ($company->promotion == 1) {
                if ($contract->period == 3) {

                    $prepayment = $company->settings->promotion_percent / 100 * $contract->total;
                    $month_discount = $company->settings['discount_' . $contract->period] / 100; // ??

                    // снимаем первый платеж с ЛС
                    if ($buyer->settings->personal_account >= $prepayment) {
                        $buyer->settings->personal_account -= $prepayment;

                        // возвращаем лимит за первый месяц
                        $buyer->settings->balance += $contract->schedule[0]->price;

                        // отнимаем первый платеж из баланса
                        $contract->balance -= $prepayment;
                        $contract->save();

                        if ($buyer->settings->save()) {

                            // сразу закрываем первый месяц
                            $contract->schedule[0]->status = 1;
                            $contract->schedule[0]->balance = 0;
                            $contract->schedule[0]->paid_at = time();
                            $contract->schedule[0]->save();

                            // записать первый платеж как транзакцию в payments
                            $pay = new Payment;
                            $pay->schedule_id = $contract->schedule[0]->id;
                            $pay->type = 'auto';
                            $pay->order_id = $contract->order_id;
                            $pay->contract_id = $contract->id;
                            $pay->amount = $prepayment;
                            $pay->user_id = $buyer->id;
                            $pay->payment_system = 'ACCOUNT';
                            $pay->status = 1;
                            $pay->save();
                        }
                    }
                }
            }

            $buyer->settings->balance -= $contract->order->credit - $contract->deposit; // снять после подтверждения смс кода
            $buyer->settings->personal_account -= $contract->deposit; // снять после подтверждения смс кода
            $buyer->settings->save();

            // IF DEPOSIT
            if ($contract->deposit > 0) {

                // записать как транзакцию в payments
                $payment = new Payment;
                $payment->schedule_id = $contract->schedule[0]->id;
                $payment->type = 'auto';
                $payment->order_id = $contract->order->id;
                $payment->contract_id = $contract->id;
                $payment->amount = $contract->deposit;
                $payment->user_id = $buyer->id;
                $payment->payment_system = 'DEPOSIT';
                $payment->status = 1;
                $payment->save();
            }
            ContractVerifyService::instantVerification($contract);

            $this->result['contract_id'] = $contract->id;
            $this->result['response']['message'] = __('api.check_sms_contract_success');

        } else {
            $this->result['status'] = 'error';
            $this->result['response']['message'] = 'wrong sms code';
        }

        return $this->result();
    }


    /**
     * начисления бонусов продавцу
     *
     *
     * @param $params
     * @return mixed
     */
    public static function setSellerBonuses($contract_id)
    {
        // если у продавца есть неначисленные бонусы за этот договор, начислим, создадим транзакцию
        if ($bonus = SellerBonus::where(['contract_id' => $contract_id, 'status' => 0])->first()) {
            $bonus->buyer->settings->zcoin += $bonus->amount;
            $bonus->buyer->settings->save();
            $bonus->status = 1;
            $bonus->save();

            // записать как транзакцию в payments
            $payment = new Payment;
            $payment->type = 'upay';
            $payment->amount = $bonus->amount;
            $payment->user_id = $bonus->seller_id;
            $payment->payment_system = 'Paycoin';
            $payment->status = 1;
            $payment->save();

            $result['status'] = 'success';

            Log::channel('contracts')->info("Начисление бонусов при подтверждении договора");
            Log::channel('contracts')->info("Договор : " . $contract_id . 'Продавец : ' . $bonus->seller_id);

        } else {
            $result['status'] = 'error';
        }

        return $result;
    }

    /**
     * отмена бонусов продавцу - при отмене договора
     *
     *
     * @param $params
     * @return mixed
     */
    public static function unsetSellerBonuses($contract_id)
    {
        // если у продавца были начисленные бонусы за этот договор, вычтем, создадим минусовую транзакцию
        if ($bonus = SellerBonus::where(['contract_id' => $contract_id, 'status' => 1])->first()) {
            $bonus->buyer->settings->zcoin -= $bonus->amount;
            $bonus->buyer->settings->save();
            $bonus->status = -1;
            $bonus->save();

            // записать как минусовую транзакцию в payments
            $payment = new Payment;
            $payment->type = 'refund';  // отмена
            $payment->amount = -1 * $bonus->amount;
            $payment->user_id = $bonus->seller_id;;
            $payment->payment_system = 'Paycoin';
            $payment->status = 1;
            $payment->save();

            $result['status'] = 'success';

            Log::channel('contracts')->info("Отмена бонусов по причине отмене договора");
            Log::channel('contracts')->info("Договор : " . $contract_id . 'Продавец : ' . $bonus->seller_id);

        } else {
            $result['status'] = 'error';
        }

        return $result;
    }


    /**
     * @OA\Get(
     *      path="/buyers/calculate-price",
     *      operationId="compatible-calc-price",
     *      tags={"Buyer compatible old test API"},
     *      summary="Calculate price",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *      @OA\Parameter(
     *          name="sum",
     *          description="Amount credit",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="credit_limit",
     *          description="Credit limit",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *       ),
     *      @OA\Response(
     *       response=201,
     *       description="Success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    public function CalculatePrice(Request $request)
    {
        $partner = Auth::user();
        $bErr = false;
        $limit = 3000000;
        $result = [
            'result' => [
                'status' => 0,
            ],
            'error' => 'null'
        ];
        if (!$request->has('sum')) {
            $result['error'] = __('api.calculate_empty_sum', ['sum' => '']);
            $bErr = true;
        } elseif ((int)$request->sum < 0) {
            $result['error'] = __('api.calculate_sum_more_zero', ['sum' => $request->sum]);
            $bErr = true;
        } elseif ($request->sum > $limit) {
            $result['error'] = __('api.calculate_sum_max_limit', ['limit' => $limit, 'sum' => $request->sum]);
            $bErr = true;
        }
        if (!$request->has('credit_limit')) {
            $result['error'] = __('api.calculate_empty_credit_limit', ['credit_limit' => '']);
            $bErr = true;
        } elseif ($request->credit_limit != 3 && $request->credit_limit != 6 && $request->credit_limit != 9 && $request->credit_limit != 12) {
            $result['error'] = __('api.calculate_credit_limit_max_limit', ['credit_limit' => $request->credit_limit]);
            $bErr = true;
        }
        if ($partner->hasRole('partner') && !$bErr) {
            $calculate = new OrderController();
            $params = [
                'type' => 'credit',
                'period' => $request->credit_limit,
                'products' => [$partner->company_id => [
                    ['amount' => 1, 'price' => $request->sum]
                ]
                ]
            ];
            $payment = $calculate->calculate($params);
            if ($payment['data']['price']['total'] > 0) {
                $result['result']['status'] = 1;
                $result['result']['test_price'] = $payment['data']['price']['total'];
                $result['result']['credit_limit'] = $request->credit_limit;
            }
        } elseif (!$bErr) {
            $result['error'] = __('api.calculate_partner_not_found');
        }
        return $result;
    }

    public function CancelContract(Request $request)
    {
        $user = Auth::user();

        $result = [
            'result' => [
                'status' => 0
            ]
        ];

        $partner = Partner::find($user->id);


        if ($request->has('buyer_phone')) {
            $buyer = Buyer::where('phone', $request->buyer_phone)->whereIn('status',[1,2,4,12])->first();
            if (!$buyer) {
                $result['result']['status'] = 0;
                $result['error'] = 'buyer not found or not verified';
                return $result;
            }

        } else {
            $result['result']['status'] = 0;
            $result['error'] = 'empty buyer_phone';
            return $result;
        }


        if (!$request->has('contract_id')) {
            $result['result']['status'] = 0;
            $result['error'] = 'empty contract_id';
            return $result;
        }

        $contract = Contract::where(['order_id' => $request->contract_id])->whereIn('status', [1, 2])->with('order')->first();

        if(!isset($contract)) {
            $result['result']['status'] = 0;
            $result['error'] = 'contract not found';
            return $result;
        }

        if(Carbon::now()->diffInMonths($contract->confirmed_at) > 1){
            $result['result']['status'] = 0;
            $result['error'] =  __('billing/order.text_expired');
            return $result;
        }

        elseif ($contract->partner_id == $partner->id) {
            $contract->canceled_at = date('Y-m-d H:i:s');
            $contract->status = 5;
            $contract->order->status = 5;

            $limit = $contract->order->credit - $contract->deposit;

            if(isset($contract->price_plan) && $contract->price_plan->is_mini_loan) {
                //мини лимит
                $buyer->settings->mini_balance += $limit;  // вернуть лимит
            }
            else {
                $buyer->settings->balance += $limit;  // вернуть лимит
            }


            if ($contract->deposit > 0) $buyer->settings->personal_account += $contract->deposit; // вернуть депозит на ЛС, если он был

            //TODO: продумать идентификатор контрактов по 3мес акции, к моменту отмены договора акция уже могла быть уже выключена
            //если была предварительная оплата по акции на 3 мес, вернуть деньги на ЛС
            if ($contract->schedule[0]->status == 1) {  // если месяц оплачен, проверим, была ли акция

                //если дата первого оплаченного месяца совпадает с датой подтверждения контракта (confirmed_at), значит была акция
                $confirmed_at = Carbon::parse($contract->confirmed_at)->format('dm');
                $paid_at = Carbon::parse($contract->schedule[0]->paid_at)->format('dm');

                if ($confirmed_at == $paid_at) {

                    $buyer->settings->personal_account += $contract->schedule[0]->total; // вернуть деньги на ЛС

                    // записать отмену транзакции в payments
                    $pay = new Payment;
                    $pay->schedule_id = $contract->schedule[0]->id;
                    $pay->type = 'refund'; // отмена
                    $pay->order_id = $contract->order_id;
                    $pay->contract_id = $contract->id;
                    $pay->amount = -1 * $contract->schedule[0]->total;
                    $pay->user_id = $buyer->id;
                    $pay->payment_system = 'ACCOUNT';
                    $pay->status = 1;
                    $pay->save();
                }
            }

            $contract->save();
            $contract->order->save();
            $buyer->settings->save();

            //MFO Отмена договора
            if($contract->general_company_id === GeneralCompany::MFO_COMPANY_ID) {
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

            $result['result']['status'] = 1;
            $result['message'] = 'contract has been canceled';
        }


        $contract_date = date('Y.m.d', strtotime($contract->created_at));
        $msg = 'resusNasiya / ' . $contract_date . ' da rasmiylashtirilgan '
            . $contract->id
            . ' shartnomangiz bekor qilindi. Tel: ' . callCenterNumber(2);

        [$sms, $http_code] = SmsHelper::sendSms($buyer->phone, $msg);
        if (($http_code === 200) || ($sms === SmsHelper::SMS_SEND_SUCCESS)) {
            Log::channel('contracts')->info('Отправка смс кода клиенту ' . $buyer->phone . ' об отмене контракта ' . $contract->id . ' Партнер ' . $partner->id);
            Log::channel('contracts')->info($request->phone . ': ' . $msg);
        } else {
            Log::channel('contracts')->info("НЕ отправлен смс клиенту об отмене контракта");
        }


        $result['contract_id'] = $contract->id;

        return $result;

    }

    /**
     * примечание!!
     * все перепутано - в апи связанном с договорами - contract_id это оrder_id, и наоборот
     *
     * возвращает оrder_id, который на самом деле contract_id
     *
     * потому что
     *
     * @param Request $request
     * @return array|false|string
     */
    public function getId(Request $request)
    {
        $user = Auth::user();


        $result = [
            'result' => [
                'status' => 0
            ]
        ];

        if (!$request->has('contract_id')) {
            $result['result']['status'] = 0;
            $result['error'] = 'empty contract_id';

        } else {
            //$partner = Partner::find($user->id);
            if ($contract = Contract::where(['order_id' => $request->contract_id])->first()) {
                $result['result']['status'] = 1;
                $result['order_id'] = $contract->id;
            } else {
                $result['result']['status'] = 0;
                $result['error'] = 'contract not found';
            }

        }


        return $result;

    }

}
