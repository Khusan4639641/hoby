<?php

namespace App\Http\Controllers\Partners;

use App\Http\Controllers\V3\CoreController;
use App\Http\Requests\AddOrderV3Request;
use App\Http\Requests\OrderCalculateV3Request;
use App\Http\Requests\OrderListV3Request;
use App\Services\API\V3\Partners\OrderService;

class OrderController extends CoreController
{
    protected OrderService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new OrderService();
    }

    /**
     * @OA\GET(
     *      path="/order/list",
     *      tags={"Order"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод который будет возвращать список договоров",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="status[]",
     *          description="Status of contract",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="cancellation_status",
     *          description="Orders for sent cancellation (1)",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="partner_id",
     *          description="Partner ID",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={
     *                   "status": "success",
     *                   "error": {},
     *                   "data": {
     *                       "current_page": 1,
     *                       "data": {
     *                           {
     *                               "id": 63003,
     *                               "user_id": 370060,
     *                               "partner_id": 235277,
     *                               "total": 2630400,
     *                               "company_id": 215357,
     *                               "partner_total": 2055000,
     *                               "credit": 2055000,
     *                               "debit": 0,
     *                               "status": 5,
     *                               "test": 0,
     *                               "created_at": "05.01.2022",
     *                               "updated_at": "2022-01-05T16:30:15.000000Z",
     *                               "city": null,
     *                               "region": null,
     *                               "area": null,
     *                               "address": null,
     *                               "shipping_code": null,
     *                               "shipping_price": 0,
     *                               "online": 0,
     *                               "manager_request": 0,
     *                               "permissions": {
     *                                   "detail",
     *                                   "delete"
     *                               },
     *                               "totalDebt": 0,
     *                               "isCancelBtnShow": 1,
     *                               "status_caption": "Bekor qilindi",
     *                               "contract": {
     *                                   "id": 62951,
     *                                   "user_id": 370060,
     *                                   "company_id": 215357,
     *                                   "partner_id": 235277,
     *                                   "order_id": 63003,
     *                                   "deposit": 0,
     *                                   "total": "2630400.00",
     *                                   "balance": "2630400.00",
     *                                   "period": 6,
     *                                   "status": 9,
     *                                   "recovery": 0,
     *                                   "cancel_act_status": 0,
     *                                   "cancel_reason": "9482",
     *                                   "canceled_at": "2022-01-05 21:30:15",
     *                                   "act_status": 1,
     *                                   "imei_status": 3,
     *                                   "client_status": 3,
     *                                   "prefix_act": 528,
     *                                   "offer_preview": null,
     *                                   "confirmation_code": "0502",
     *                                   "confirmed_at": "05.01.2022",
     *                                   "created_at": "05.01.2022 21:20:06",
     *                                   "updated_at": "2022-01-05T16:30:15.000000Z",
     *                                   "date_recovery_start": null,
     *                                   "doc_path": 1,
     *                                   "is_allowed_online_signature": 0,
     *                                   "cancellation_status": 0,
     *                                   "expired_days": 58,
     *                                   "general_company_id": 1,
     *                                   "contract_cancellation_reason": null,
     *                                   "autopay_status": 1,
     *                                   "ox_system": 0,
     *                                   "status_caption": "Yopiq",
     *                                   "debts": {}
     *                               },
     *                               "buyer": {
     *                                   "id": 370060,
     *                                   "email": null,
     *                                   "name": "Xolmatov",
     *                                   "surname": "Qobuljon",
     *                                   "patronymic": "Yuldash O‘g‘li",
     *                                   "gender": 1,
     *                                   "phone": "+7155580",
     *                                   "birth_date": "1991-05-21",
     *                                   "region": 26,
     *                                   "local_region": 203,
     *                                   "email_verified_at": null,
     *                                   "token_generated_at": "2022-01-05 21:11:32",
     *                                   "verify_message": null,
     *                                   "status": 4,
     *                                   "status_employee": null,
     *                                   "company_id": null,
     *                                   "seller_company_id": null,
     *                                   "created_at": "2022-01-05T16:11:32.000000Z",
     *                                   "created_by": 235277,
     *                                   "updated_at": "2022-01-05T16:20:49.000000Z",
     *                                   "verified_at": null,
     *                                   "verified_by": null,
     *                                   "ticketit_admin": 0,
     *                                   "ticketit_agent": 0,
     *                                   "kyc_status": 3,
     *                                   "kyc_id": 239916,
     *                                   "is_saller": 0,
     *                                   "device_os": null,
     *                                   "lang": null,
     *                                   "firebase_token_android": null,
     *                                   "firebase_token_ios": null,
     *                                   "doc_path": 1,
     *                                   "black_list": 0,
     *                                   "vip": 0,
     *                                   "role_id": 12
     *                               },
     *                               "products": {
     *                                   {
     *                                       "id": 84894,
     *                                       "order_id": 63003,
     *                                       "product_id": null,
     *                                       "vendor_code": "",
     *                                       "name": "Smartfon Oppo A15S IMEI: 867690050094358",
     *                                       "price": 2630400,
     *                                       "price_discount": 2055000,
     *                                       "amount": 1,
     *                                       "weight": 0,
     *                                       "category_id": 1,
     *                                       "imei": "867690050094358",
     *                                       "created_at": "2022-01-05T16:20:06.000000Z",
     *                                       "updated_at": "2022-01-05T16:20:06.000000Z",
     *                                       "info": null
     *                                   }
     *                               }
     *                           }
     *                       },
     *                       "first_page_url": "https://test.test.uz/api/v3/order/list?page=1",
     *                       "from": 1,
     *                       "last_page": 1,
     *                       "last_page_url": "https://test.test.uz/api/v3/order/list?page=1",
     *                       "next_page_url": null,
     *                       "path": "https://test.test.uz/api/v3/order/list",
     *                       "per_page": 15,
     *                       "prev_page_url": null,
     *                       "to": 13,
     *                       "total": 13
     *                   }
     *               })
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                      "status": "error",
     *                      "error": {},
     *                      "data": {}
     *                  }
     *                 )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         "message": "Unauthenticated"
     *                     }
     *                 )
     *          )
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         "message": "Forbidden"
     *                     }
     *                 )
     *              )
     *          )
     *     )
     */
    public function list(OrderListV3Request $request)
    {
        return $this->service->list($request);
    }

    /**
     * @OA\Post(
     *      path="/order/calculate",
     *      tags={"Order"},
     *      security={{"api_token_security":{}}},
     *      summary="Калькулятор который считает стоимость товара с наценкой",
     *      description="Return json",
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="user_id",
     *                      type="integer"
     *                  ),
     *                  @OA\Property(
     *                      property="period",
     *                      type="integer"
     *                  ),
     *                  @OA\Property(
     *                      property="products",
     *                      type="json"
     *                  ),
     *                  example={
     *                       "user_id": 372704,
     *                       "period": 6,
     *                       "products": {
     *                               {
     *                                   "amount": "1",
     *                                   "name": "Product 1",
     *                                   "imei": "213421341234134",
     *                                   "price": "30"
     *                               },
     *                               {
     *                                   "amount": "1",
     *                                   "name": "Product 2",
     *                                   "imei": "213421341234134",
     *                                   "price": "50"
     *                               },
     *                               {
     *                                   "amount": "1",
     *                                   "name": "Product 3",
     *                                   "imei": "213421341234134",
     *                                   "price": "120"
     *                               }
     *                       }
     *                   }
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={
     *                  "status": "success",
     *                  "error": {},
     *                  "data": {
     *                      "total": 256,
     *                      "shipping": 0,
     *                      "origin": 200,
     *                      "month": 42.67,
     *                      "partner": 200,
     *                      "deposit": 0,
     *                      "products": {
     *                          {
     *                              "amount": "1",
     *                              "name": "Product 1",
     *                              "imei": "213421341234134",
     *                              "price": 30
     *                          },
     *                          {
     *                              "amount": "1",
     *                              "name": "Product 2",
     *                              "imei": "213421341234134",
     *                              "price": 50
     *                          },
     *                          {
     *                              "amount": "1",
     *                              "name": "Product 3",
     *                              "imei": "213421341234134",
     *                              "price": 120
     *                          }
     *                      },
     *                      "amount": 3,
     *                      "contract": {
     *                          "payments": {
     *                              {
     *                                  "total": 42.67,
     *                                  "origin": 33.33
     *                              },
     *                              {
     *                                  "total": 42.67,
     *                                  "origin": 33.33
     *                              },
     *                              {
     *                                  "total": 42.67,
     *                                  "origin": 33.33
     *                              },
     *                              {
     *                                  "total": 42.67,
     *                                  "origin": 33.33
     *                              },
     *                              {
     *                                  "total": 42.67,
     *                                  "origin": 33.33
     *                              },
     *                              {
     *                                  "total": 42.65,
     *                                  "origin": 33.35
     *                              }
     *                          }
     *                      }
     *                  }
     *              })
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                   example={
     *                     "status": "error",
     *                     "error": {
     *                         {
     *                             "type": "danger",
     *                             "text": "period maydoni to`ldirilgan bo`lishi shart."
     *                         }
     *                     },
     *                     "data": {}
     *                 }
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         "message": "Unauthenticated"
     *                     }
     *                 )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         "message": "Forbidden"
     *                     }
     *                 )
     *              ),
     *          )
     *     )
     */
    public function calculate(OrderCalculateV3Request $request)
    {
        return $this->service->calculate($request);
    }

    /**
     * @OA\Post(
     *      path="/order/calculate-bonus",
     *      tags={"Order"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод который считает бонусов продавца от стоимости товара",
     *      description="Return json",
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="user_id",
     *                      type="integer"
     *                  ),
     *                  @OA\Property(
     *                      property="period",
     *                      type="integer"
     *                  ),
     *                  @OA\Property(
     *                      property="products",
     *                      type="json"
     *                  ),
     *                  example={
     *                       "user_id": 372704,
     *                       "period": 6,
     *                       "products": {
     *                               {
     *                                   "amount": "1",
     *                                   "name": "Product 1",
     *                                   "imei": "213421341234134",
     *                                   "price": "30"
     *                               },
     *                               {
     *                                   "amount": "1",
     *                                   "name": "Product 2",
     *                                   "imei": "213421341234134",
     *                                   "price": "50"
     *                               },
     *                               {
     *                                   "amount": "1",
     *                                   "name": "Product 3",
     *                                   "imei": "213421341234134",
     *                                   "price": "120"
     *                               }
     *                       }
     *                   }
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={
     *                   "status": "success",
     *                   "error": {},
     *                   "data": {
     *                       "bonus_amount": 0
     *                   }
     *               })
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                   example={
     *                     "status": "error",
     *                     "error": {
     *                         {
     *                             "type": "danger",
     *                             "text": "period maydoni to`ldirilgan bo`lishi shart."
     *                         }
     *                     },
     *                     "data": {}
     *                 }
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         "message": "Unauthenticated"
     *                     }
     *                 )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         "message": "Forbidden"
     *                     }
     *                 )
     *              ),
     *          )
     *     )
     */
    public function calculateBonus(AddOrderV3Request $request)
    {
        return $this->service->calculateBonus($request);
    }

    /**
     * @OA\Post(
     *      path="/orders/add",
     *      tags={"Order"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод для создания договора",
     *      description="Return json",
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="user_id",
     *                      type="integer"
     *                  ),
     *                  @OA\Property(
     *                      property="period",
     *                      type="integer"
     *                  ),
     *                  @OA\Property(
     *                      property="products",
     *                      type="json"
     *                  ),
     *                  example={
     *                       "user_id": 372704,
     *                       "period": 6,
     *                       "products": {
     *                               {
     *                                   "amount": "1",
     *                                   "name": "Product 1",
     *                                   "imei": "213421341234134",
     *                                   "price": "30"
     *                               },
     *                               {
     *                                   "amount": "1",
     *                                   "name": "Product 2",
     *                                   "imei": "213421341234134",
     *                                   "price": "50"
     *                               },
     *                               {
     *                                   "amount": "1",
     *                                   "name": "Product 3",
     *                                   "imei": "213421341234134",
     *                                   "price": "120"
     *                               }
     *                       }
     *                   }
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={
     *                  "status": "success",
     *                  "error": {},
     *                  "data": {
     *                      "status": "success",
     *                      "order": {
     *                          "id": 65265,
     *                          "user_id": 372704,
     *                          "partner_id": 235277,
     *                          "total": 256,
     *                          "company_id": 215357,
     *                          "partner_total": 200,
     *                          "credit": 200,
     *                          "debit": 0,
     *                          "status": 0,
     *                          "test": 0,
     *                          "created_at": "26.07.2022",
     *                          "updated_at": "2022-07-26T08:18:19.000000Z",
     *                          "city": null,
     *                          "region": null,
     *                          "area": null,
     *                          "address": null,
     *                          "shipping_code": null,
     *                          "shipping_price": 0,
     *                          "online": 0,
     *                          "status_caption": "Yangi",
     *                          "company": {
     *                              "id": 215357,
     *                              "inn": "307312824",
     *                              "name": "OOO «MEDIAPARK GROUP»",
     *                              "description": null,
     *                              "region_id": 1726,
     *                              "address": "г.Ташкент Юнусабадский район 19-квартал ул. Юнусота дом 119",
     *                              "legal_address": "г.Ташкент, Шайхантахурский р-н,ул. Караташ, д.11-А",
     *                              "bank_name": "«Капитал Банк» Чорсу ф-л",
     *                              "payment_account": "20208000505210242002",
     *                              "status": 1,
     *                              "type": null,
     *                              "created_at": "09.09.2021",
     *                              "updated_at": "2021-12-24T05:30:54.000000Z",
     *                              "parent_id": 215339,
     *                              "k_vendor": 1,
     *                              "website": "MEDIAPARK.UZ",
     *                              "nds_numder": "326050100989",
     *                              "mfo": "01033",
     *                              "oked": "46690",
     *                              "phone": "998712033333",
     *                              "manager_phone": null,
     *                              "short_description": null,
     *                              "email": null,
     *                              "brand": "MEDIAPARK YUNUSOBOD",
     *                              "working_hours": null,
     *                              "seller_coefficient": 1,
     *                              "uniq_num": 91,
     *                              "date_pact": "2021-08-10T19:00:00.000000Z",
     *                              "lat": null,
     *                              "lon": null,
     *                              "reverse_calc": 0,
     *                              "is_allowed_online_signature": 0,
     *                              "manager_id": null,
     *                              "vip": 0,
     *                              "general_company_id": 1,
     *                              "promotion": 0
     *                          },
     *                          "products": {
     *                              {
     *                                  "id": 87710,
     *                                  "order_id": 65265,
     *                                  "product_id": null,
     *                                  "vendor_code": "",
     *                                  "name": "Product 1 IMEI: 213421341234134",
     *                                  "price": 38.4,
     *                                  "price_discount": 30,
     *                                  "amount": 1,
     *                                  "weight": 0,
     *                                  "category_id": null,
     *                                  "imei": "213421341234134",
     *                                  "created_at": "2022-07-26T08:18:19.000000Z",
     *                                  "updated_at": "2022-07-26T08:18:19.000000Z",
     *                                  "info": null
     *                              },
     *                              {
     *                                  "id": 87711,
     *                                  "order_id": 65265,
     *                                  "product_id": null,
     *                                  "vendor_code": "",
     *                                  "name": "Product 2 IMEI: 213421341234134",
     *                                  "price": 64,
     *                                  "price_discount": 50,
     *                                  "amount": 1,
     *                                  "weight": 0,
     *                                  "category_id": null,
     *                                  "imei": "213421341234134",
     *                                  "created_at": "2022-07-26T08:18:19.000000Z",
     *                                  "updated_at": "2022-07-26T08:18:19.000000Z",
     *                                  "info": null
     *                              },
     *                              {
     *                                  "id": 87712,
     *                                  "order_id": 65265,
     *                                  "product_id": null,
     *                                  "vendor_code": "",
     *                                  "name": "Product 3 IMEI: 213421341234134",
     *                                  "price": 153.6,
     *                                  "price_discount": 120,
     *                                  "amount": 1,
     *                                  "weight": 0,
     *                                  "category_id": null,
     *                                  "imei": "213421341234134",
     *                                  "created_at": "2022-07-26T08:18:19.000000Z",
     *                                  "updated_at": "2022-07-26T08:18:19.000000Z",
     *                                  "info": null
     *                              }
     *                          },
     *                          "contract": {
     *                              "id": 1234803,
     *                              "user_id": 372704,
     *                              "company_id": 215357,
     *                              "partner_id": 235277,
     *                              "order_id": 65265,
     *                              "deposit": 0,
     *                              "total": "256.00",
     *                              "balance": "256.00",
     *                              "period": 6,
     *                              "status": 0,
     *                              "recovery": 0,
     *                              "cancel_act_status": 0,
     *                              "cancel_reason": null,
     *                              "canceled_at": null,
     *                              "act_status": 0,
     *                              "imei_status": 0,
     *                              "client_status": 0,
     *                              "prefix_act": 541,
     *                              "offer_preview": null,
     *                              "confirmation_code": null,
     *                              "confirmed_at": "26.07.2022",
     *                              "created_at": "26.07.2022 13:18:19",
     *                              "updated_at": "2022-07-26T08:18:19.000000Z",
     *                              "date_recovery_start": null,
     *                              "doc_path": 1,
     *                              "is_allowed_online_signature": 0,
     *                              "cancellation_status": 0,
     *                              "expired_days": 0,
     *                              "general_company_id": 1,
     *                              "contract_cancellation_reason": null,
     *                              "autopay_status": null,
     *                              "ox_system": 0,
     *                              "status_caption": "Moderatsiya"
     *                          }
     *                      },
     *                      "nds": 0.15,
     *                      "status_list": {
     *                          0,
     *                          1,
     *                          2,
     *                          3,
     *                          4,
     *                          5,
     *                          7,
     *                          8,
     *                          9
     *                      },
     *                      "qrcode": "<img src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHsAAAB7AQMAAABuCW08AAAABlBMVEX///8AAABVwtN+AAAACXBIWXMAAA7EAAAOxAGVKw4bAAABaklEQVRIid3Usa2EMAwGYEcu6MICkVgjXVbiFgCyQFgpXdaIlAWgSxHhZ8R7d2X82oso0Fdwvt92AL7pjESekBIGR5cMNOA2ZJVos/wuA1c8wVqnzVIQQ3AYbNP/Apu1NbMcAH3Fyxr4lN4BziPYcj+fgDrAR0U6Ix6f4Dswxums5GsJ0ISgKteInqYDyiGDu7epvRKbmWWgwWgHK/E44CaDMRJV3FNeLCwyAGvW2l78m04KGjgPnruyU55lMNbGg+CpXNwrGSi681aEPj7f6IMeYAbcKx7OLDJQlS7bxtRmyFoG4PJKHCS86pN6H1TkFmWAQum39C6MNat76PI8NCHwdvrYYOAyjRQGAw65S4pok8FY6Uy0V/6Lf5X24P6MQ6p5gae3feBt2CO/msW+F6gDfH/s8Z6FBd5XTg9c2SBz9u/BlYBPvHM8RyWIgVeBH4rlEgJg4JvDlpOmTQZ3Hnyp8wRRm2XwPecHf1lw64Uv0ycAAAAASUVORK5CYII='/>",
     *                      "offer_pdf": "/storage/contract/1234803/vendor_offer_1234803.pdf",
     *                      "account_pdf": "/storage/contract/1234803/buyer_account_1234803.pdf"
     *                  }
     *              })
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                   example={
     *                     "status": "error",
     *                     "error": {
     *                         {
     *                             "type": "danger",
     *                             "text": "period maydoni to`ldirilgan bo`lishi shart."
     *                         }
     *                     },
     *                     "data": {}
     *                 }
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         "message": "Unauthenticated"
     *                     }
     *                 )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         "message": "Forbidden"
     *                     }
     *                 )
     *              ),
     *          )
     *     )
     */
    public function add(AddOrderV3Request $request)
    {
        $this->service->validateOrderAdd($request);
        return $this->service->add($request);
    }
}
