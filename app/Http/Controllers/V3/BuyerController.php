<?php

namespace App\Http\Controllers\V3;

use App\Http\Requests\V3\Buyer\UploadAddressRequest;
use App\Models\Buyer;
use App\Models\Contract;
use App\Services\API\V3\BuyerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class BuyerController extends CoreController
{
    protected BuyerService $service;

    public function __construct()
    {
        parent::__construct();
        $this->model = app(Buyer::class);
        //Eager load
        $this->loadWith = ['settings', 'personals', 'personals.passport_selfie'];
        // Relation для данных по ID Карте
        array_push($this->loadWith, 'personals.latest_id_card_or_passport_photo');
        $this->service = new BuyerService();
    }

    public function single($id, $with = [])
    {
        $single = parent::single($id, array_merge($this->loadWith, $with));
        $single->status_list = Config::get('test.order_status');
        foreach ($single->debts as $debt)
            $single->totalDebt += $debt->total;
        return $single;
    }

    /**
     * @OA\Get(
     *      path="/buyer/change-lang",
     *      tags={"Buyer"},
     *      summary="Изменить язык пользователя (Change user language)",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *      @OA\Parameter(
     *          name="lang",
     *          description="Language of user interface",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string",
     *              enum={"ru","uz"},
     *              default={"ru"}
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
     *                 @OA\Schema(
     *                     example={"code":1,"error":{},"data":{}}
     *                 )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"code":0,"error":{{"type":"danger","text":"lang is not set"}},"data":{}}
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
    public function changeLang(Request $request)
    {
        return $this->service::changeLang($request);
    }

    /**
     * @OA\Get(
     *      path="/buyer/catalog/list",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Лист категории (List of categories)",
     *      description="Return json",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={
     *                  "status": "success",
     *                  "error": {},
     *                  "data": {
     *                      {
     *                          "id": 1,
     *                          "img": "",
     *                          "ru": {
     *                              "id": 1,
     *                              "language_code": "ru",
     *                              "category_id": 1,
     *                              "title": "Телефоны и смартфоны",
     *                              "slug": "11",
     *                              "preview_text": "11",
     *                              "detail_text": "11",
     *                              "created_at": null,
     *                              "updated_at": "2021-08-06T06:01:46.000000Z"
     *                          },
     *                          "uz": {
     *                              "id": 2,
     *                              "language_code": "uz",
     *                              "category_id": 1,
     *                              "title": "Telefon va smartfonlar",
     *                              "slug": "11",
     *                              "preview_text": "11",
     *                              "detail_text": "11",
     *                              "created_at": null,
     *                              "updated_at": "2021-08-06T06:01:46.000000Z"
     *                          }
     *                      }
     *                  }
     *              })
     *          )
     *       ),
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
    public function catalog(Request $request)
    {
        return $this->service::catalog($request);
    }

    /**
     * @OA\Get(
     *      path="/buyer/detail",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Подробная информация о категории (Category detailed)",
     *      description="Return json",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={
     *                  "status": "success",
     *                  "error": {},
     *                  "data": {
     *                      "id": 372390,
     *                      "email": null,
     *                      "name": null,
     *                      "surname": null,
     *                      "patronymic": null,
     *                      "gender": null,
     *                      "phone": "+998900626204",
     *                      "birth_date": null,
     *                      "region": null,
     *                      "local_region": null,
     *                      "email_verified_at": null,
     *                      "token_generated_at": "2022-06-03 17:51:56",
     *                      "verify_message": null,
     *                      "status": 2,
     *                      "status_employee": null,
     *                      "company_id": null,
     *                      "seller_company_id": null,
     *                      "created_at": "2022-06-03T06:16:59.000000Z",
     *                      "created_by": null,
     *                      "updated_at": "2022-06-08T07:34:31.000000Z",
     *                      "verified_at": null,
     *                      "verified_by": null,
     *                      "ticketit_admin": 0,
     *                      "ticketit_agent": 0,
     *                      "kyc_status": 3,
     *                      "kyc_id": null,
     *                      "is_saller": 0,
     *                      "device_os": null,
     *                      "lang": "uz",
     *                      "firebase_token_android": "cnghUitQR4edZg4UMMEn7s:APA91bGUeAgsAVeUX64N0CrDloF9TO4qhYusT5UCmeHmSTgD5F7pr4PGdD9soCzrZAvWi89DYa4M292WU-v2RtRbzHfAtp-CzTqQL9dWN7FC5tq2-YeTLzOBPSno_1CJQcBNbjqgO5jY",
     *                      "firebase_token_ios": null,
     *                      "doc_path": 1,
     *                      "black_list": 0,
     *                      "vip": 0,
     *                      "permissions": {
     *                          "detail",
     *                          "modify"
     *                      },
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
     *                      "totalDebt": 0,
     *                      "settings": {
     *                          "id": 316827,
     *                          "user_id": 372390,
     *                          "period": 12,
     *                          "limit": 9000000,
     *                          "balance": 9000000,
     *                          "rating": 0,
     *                          "zcoin": 0,
     *                          "paycoin": 0,
     *                          "paycoin_month": 0,
     *                          "paycoin_sale": 0,
     *                          "paycoin_limit": 0,
     *                          "personal_account": 0,
     *                          "katm_region_id": 0,
     *                          "katm_local_region_id": 0,
     *                          "created_at": "2022-06-03T06:17:28.000000Z",
     *                          "updated_at": "2022-06-03T06:17:28.000000Z"
     *                      },
     *                      "personals": {
     *                          "id": 364653,
     *                          "user_id": 372390,
     *                          "birthday": null,
     *                          "city_birth": null,
     *                          "work_company": null,
     *                          "work_phone": null,
     *                          "passport_number": null,
     *                          "passport_number_hash": null,
     *                          "passport_date_issue": null,
     *                          "passport_issued_by": null,
     *                          "passport_expire_date": null,
     *                          "passport_type": 6,
     *                          "home_phone": null,
     *                          "pinfl": null,
     *                          "pinfl_hash": null,
     *                          "pinfl_status": 1,
     *                          "inn": null,
     *                          "mrz": null,
     *                          "social_vk": null,
     *                          "social_facebook": null,
     *                          "social_linkedin": null,
     *                          "social_instagram": null,
     *                          "vendor_link": null,
     *                          "created_at": "2022-06-03T06:16:59.000000Z",
     *                          "updated_at": "2022-06-03T06:18:28.000000Z",
     *                          "passport_selfie": null,
     *                          "latest_id_card_or_passport_photo": null
     *                      },
     *                      "debts": {
     *                          {
     *                              "balance": "37777.78"
     *                          },
     *                          {
     *                              "balance": "368.00"
     *                          }
     *                      }
     *                  }
     *              })
     *          )
     *       ),
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
    public function detail()
    {
        $user = Auth::user();
        $buyer = $this->single($user->id);
        if (!$buyer) {
            return $this->service::handleError([__('app.err_not_found')]);
        }
        if ($user->can('detail', $buyer)) {
            return $this->service::handleResponse($buyer);
        } else {
            return $this->service::handleError([__('app.err_access_denied')]);
        }
        return $this->service::handleError([__('app.err_not_found')]);
    }

    /**
     * @OA\Post(
     *      path="/buyer/add-guarant",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод добавление доверительных лиц для покупателя (Add guarant for buyer)",
     *      description="Return json",
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="phone",
     *                      type="string"
     *                  ),
     *                  @OA\Property(
     *                      property="name",
     *                      type="string"
     *                  ),
     *                  example={"data":{{"phone":"998974708221","name":"Guarant first"},{"phone":"998974708222","name":"Guarant second"}}}
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={"code":1,"error":{},"data":{}})
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
     *                             "text": "Поле data обязательно для заполнения."
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
    public function addGuarant(Request $request)
    {
        return $this->service::addGuarant($request);
    }

    /**
     * @OA\Get(
     *      path="/buyer/check_status",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Проверить статус покупателя",
     *      description="Редирект на соответствующую страницу. (В случае если отказано верификации по какой нибудь причине, статус юзера вернется туда где было отказано. В интерфейсе будет отображаться page по статусу.)",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={"code":1,"error":{},"data":{"status":2,"buyer_id":372390,"passport_type":6}})
     *          )
     *       ),
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
    public function check_status()
    {
        return $this->service::check_status();
    }

    /**
     * @OA\Get(
     *      path="/buyer/balance",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод баланс личного счета покупателя (Personal account balance for buyer)",
     *      description="Return json",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={"code":1,"error":{},"data":{"installment":9000000,"deposit":0,"all":9000000}})
     *          )
     *       ),
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
    public function balance()
    {
        return $this->service::balance();
    }

    /**
     * @OA\Get(
     *      path="/buyer/cards",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Список платежный карт покупателя (List cards of buyer)",
     *      description="Return json",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={"code":1,"error":{},"data":{{"title":"Saidorif Kadirov","img":"card_empty.png","pan":"**** 8911","exp":"2405","id":680,"type":"UZCARD","balance":120000}}})
     *          )
     *       ),
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
    public function cards()
    {
        return $this->service::cards();
    }

    /**
     * @OA\Get(
     *      path="/buyer/payments",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Список платежей (Payments list)",
     *      description="Return json",
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
     *                               "payment_id": 232412,
     *                               "contract_id": null,
     *                               "amount": "1001.00",
     *                               "type": "user",
     *                               "payment_system": "UZCARD",
     *                               "date": "2022-06-09",
     *                               "time": "12:27:52",
     *                               "receipt_type": "Xaridor",
     *                               "category": "Пополнение личного счёта",
     *                               "category_info": "",
     *                               "created_at": "2022-06-09 12:27:52"
     *                           },
     *                           {
     *                               "payment_id": 232411,
     *                               "contract_id": null,
     *                               "amount": "1001.00",
     *                               "type": "user",
     *                               "payment_system": "UZCARD",
     *                               "date": "2022-05-31",
     *                               "time": "16:59:46",
     *                               "receipt_type": "Xaridor",
     *                               "category": "Пополнение личного счёта",
     *                               "category_info": "",
     *                               "created_at": "2022-05-31 16:59:46"
     *                           }
     *                       },
     *                       "from": 1,
     *                       "last_page": 3,
     *                       "last_page_url": "http://test.loc:8888/api/v3/buyer/payments?page=3",
     *                       "next_page_url": "http://test.loc:8888/api/v3/buyer/payments?page=2",
     *                       "path": "http://test.loc:8888/api/v3/buyer/payments",
     *                       "per_page": 2,
     *                       "prev_page_url": null,
     *                       "to": 2,
     *                       "total": 6
     *                   }
     *               })
     *          )
     *       ),
     *      @OA\Parameter(
     *          name="page",
     *          description="Page",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="limit",
     *          description="Limit of pagination items",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
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
    public function payments(Request $request)
    {
        return $this->service::payments($request);
    }

    /**
     * @OA\Get(
     *      path="/buyer/notify/list",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Список уведомлений (Notifications list)",
     *      description="Return json",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={
     *                   "status": "success",
     *                   "error": {},
     *                   "data": {
     *                       {
     *                           "id": "1bf69c2e-7c11-470f-afd1-722ae055354e",
     *                           "type": "App\\Console\\Commands\\FCMNotification",
     *                           "notifiable_type": "App\\Models\\User",
     *                           "notifiable_id": 372390,
     *                           "data": {
     *                               "type": "fcm",
     *                               "time": "13:36:40 2022-06-06",
     *                               "title_ru": "ПОГАШЕНИЕ ПО КОНТРАКТУ",
     *                               "title_uz": "SHARTNOMA BO'YICHA TO'LOV",
     *                               "message_uz": "Hurmatli mijoz, Sizga 64634  shartnoma bo'yicha 368.00 so'mni 01.06.2022 10:35:59 kuni to'lovni amalga oshirishingiz kerakligini eslatib o'tamiz.",
     *                               "message_ru": "Уважаемый клиент, напоминаем Вам о предстоящей оплате в размере 368.00 сум в 01.06.2022 10:35:59 по договору 64634."
     *                           },
     *                           "hash": null,
     *                           "read_at": null,
     *                           "created_at": null,
     *                           "updated_at": null
     *                       }
     *                   }
     *               }
     *             )
     *          )
     *       ),
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
    public function notify()
    {
        return $this->service::notify();
    }

    /**
     * @OA\Get(
     *      path="/buyer/contracts",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary=" Список всех договоров покупателя (List of buyer contracts)",
     *      description="Return json",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={
     *                      "status": "success",
     *                      "error": {},
     *                      "data": {
     *                          {
     *                              "contract_id": 64634,
     *                              "order_id": 64703,
     *                              "online": 0,
     *                              "period": 12,
     *                              "remainder": "4416.00",
     *                              "current_pay": "368.00",
     *                              "next_pay": "01.06.2022 10:35:59",
     *                              "monthly_payment": "368.00",
     *                              "status": 1,
     *                              "schedule_list": {
     *                                  {
     *                                      "id": 719346,
     *                                      "user_id": 372390,
     *                                      "contract_id": 64634,
     *                                      "price": "266.67",
     *                                      "total": "368.00",
     *                                      "balance": "368.00",
     *                                      "payment_date": "01.06.2022 10:35:59",
     *                                      "real_payment_date": "2022-06-01 10:35:59",
     *                                      "status": 0,
     *                                      "paid_at": null,
     *                                      "created_at": "2022-05-06T05:35:59.000000Z",
     *                                      "updated_at": "2022-05-06T05:35:59.000000Z"
     *                                  },
     *                                  {
     *                                      "id": 719347,
     *                                      "user_id": 371879,
     *                                      "contract_id": 64634,
     *                                      "price": "266.67",
     *                                      "total": "368.00",
     *                                      "balance": "368.00",
     *                                      "payment_date": "01.07.2022 10:35:59",
     *                                      "real_payment_date": "2022-07-01 10:35:59",
     *                                      "status": 0,
     *                                      "paid_at": null,
     *                                      "created_at": "2022-05-06T05:35:59.000000Z",
     *                                      "updated_at": "2022-05-06T05:35:59.000000Z"
     *                                  }
     *                              },
     *                              "created_at": "06.05.2022 10:35:59",
     *                              "manager_id": null
     *                          }
     *                      }
     *                  }
     *              )
     *          )
     *       ),
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
    public function contracts()
    {
        return $this->service::contracts();
    }

    /**
     * @OA\Post(
     *      path="/buyer/contract",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Контракт покупателя (Contract for buyer)",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="contract_id",
     *          description="ID of contract",
     *          required=true,
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
     *              @OA\Schema(example={"code":1,"error":{},"data":{"contracts":{"contract_id":64634,"status":1,"order_id":64703,"online":0,"remainder":"4416.00","next_pay":"01.06.2022 10:35:59","monthly_payment":"368.00","period":12,"current_pay":"368.00","doc_pdf":".\/storage\/contract\/64634\/buyer_account_64634.pdf","offer_preview":"","products":{{"id":86933,"order_id":64703,"product_id":null,"vendor_code":"","name":"test12","price":4416,"price_discount":3200,"amount":1,"weight":0,"category_id":8,"imei":null,"created_at":"2022-05-06T05:35:59.000000Z","updated_at":"2022-05-06T05:35:59.000000Z"}},"doc_path":1,"is_allowed_online_signature":1,"manager_id":null,"url":"https:\/\/ofd.soliq.uz\/check?t=UZ201125155014&r=1486&c=20220519191002&s=083054543399"},"schedule_list":{{"id":719346,"user_id":372390,"contract_id":64634,"price":"266.67","total":"368.00","balance":"368.00","payment_date":"01.06.2022 10:35:59","real_payment_date":"2022-06-01 10:35:59","status":0,"paid_at":null,"created_at":"2022-05-06T05:35:59.000000Z","updated_at":"2022-05-06T05:35:59.000000Z"},{"id":719347,"user_id":371879,"contract_id":64634,"price":"266.67","total":"368.00","balance":"368.00","payment_date":"01.07.2022 10:35:59","real_payment_date":"2022-07-01 10:35:59","status":0,"paid_at":null,"created_at":"2022-05-06T05:35:59.000000Z","updated_at":"2022-05-06T05:35:59.000000Z"},{"id":719348,"user_id":371879,"contract_id":64634,"price":"266.67","total":"368.00","balance":"368.00","payment_date":"01.08.2022 10:35:59","real_payment_date":"2022-08-01 10:35:59","status":0,"paid_at":null,"created_at":"2022-05-06T05:35:59.000000Z","updated_at":"2022-05-06T05:35:59.000000Z"},{"id":719349,"user_id":371879,"contract_id":64634,"price":"266.67","total":"368.00","balance":"368.00","payment_date":"01.09.2022 10:35:59","real_payment_date":"2022-09-01 10:35:59","status":0,"paid_at":null,"created_at":"2022-05-06T05:35:59.000000Z","updated_at":"2022-05-06T05:35:59.000000Z"},{"id":719350,"user_id":371879,"contract_id":64634,"price":"266.67","total":"368.00","balance":"368.00","payment_date":"01.10.2022 10:35:59","real_payment_date":"2022-10-01 10:35:59","status":0,"paid_at":null,"created_at":"2022-05-06T05:35:59.000000Z","updated_at":"2022-05-06T05:35:59.000000Z"},{"id":719351,"user_id":371879,"contract_id":64634,"price":"266.67","total":"368.00","balance":"368.00","payment_date":"01.11.2022 10:35:59","real_payment_date":"2022-11-01 10:35:59","status":0,"paid_at":null,"created_at":"2022-05-06T05:35:59.000000Z","updated_at":"2022-05-06T05:35:59.000000Z"},{"id":719352,"user_id":371879,"contract_id":64634,"price":"266.67","total":"368.00","balance":"368.00","payment_date":"01.12.2022 10:35:59","real_payment_date":"2022-12-01 10:35:59","status":0,"paid_at":null,"created_at":"2022-05-06T05:35:59.000000Z","updated_at":"2022-05-06T05:35:59.000000Z"},{"id":719353,"user_id":371879,"contract_id":64634,"price":"266.67","total":"368.00","balance":"368.00","payment_date":"01.01.2023 10:35:59","real_payment_date":"2023-01-01 10:35:59","status":0,"paid_at":null,"created_at":"2022-05-06T05:35:59.000000Z","updated_at":"2022-05-06T05:35:59.000000Z"},{"id":719354,"user_id":371879,"contract_id":64634,"price":"266.67","total":"368.00","balance":"368.00","payment_date":"01.02.2023 10:35:59","real_payment_date":"2023-02-01 10:35:59","status":0,"paid_at":null,"created_at":"2022-05-06T05:35:59.000000Z","updated_at":"2022-05-06T05:35:59.000000Z"},{"id":719355,"user_id":371879,"contract_id":64634,"price":"266.67","total":"368.00","balance":"368.00","payment_date":"01.03.2023 10:35:59","real_payment_date":"2023-03-01 10:35:59","status":0,"paid_at":null,"created_at":"2022-05-06T05:35:59.000000Z","updated_at":"2022-05-06T05:35:59.000000Z"},{"id":719356,"user_id":371879,"contract_id":64634,"price":"266.67","total":"368.00","balance":"368.00","payment_date":"01.04.2023 10:35:59","real_payment_date":"2023-04-01 10:35:59","status":0,"paid_at":null,"created_at":"2022-05-06T05:35:59.000000Z","updated_at":"2022-05-06T05:35:59.000000Z"},{"id":719357,"user_id":371879,"contract_id":64634,"price":"266.63","total":"368.00","balance":"368.00","payment_date":"01.05.2023 10:35:59","real_payment_date":"2023-05-01 10:35:59","status":0,"paid_at":null,"created_at":"2022-05-06T05:35:59.000000Z","updated_at":"2022-05-06T05:35:59.000000Z"}}}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(example={"code":0,"error":{{"type":"danger","text":"api.contract_not_found"}},"data":{}})
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
    public function contract(Request $request)
    {
        return $this->service::contract($request);
    }

    /**
     * @OA\Get(
     *      path="/buyer/contracts/notifications",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Список Всех нотификаций по контрактам (List of buyer contracts)",
     *      description="Return json",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={
     *                      "status": "success",
     *                      "error": {},
     *                      "data": {
     *                          {
     *                              "contract_id": 64634,
     *                              "order_id": 64703,
     *                              "online": 0,
     *                              "period": 12,
     *                              "remainder": "4416.00",
     *                              "current_pay": "368.00",
     *                              "negxt_pay": "01.06.2022 10:35:59",
     *                              "monthly_payment": "368.00",
     *                              "status": 1,
     *                              "schedule_list": {
     *                                  {
     *                                      "id": 719346,
     *                                      "user_id": 372390,
     *                                      "contract_id": 64634,
     *                                      "price": "266.67",
     *                                      "total": "368.00",
     *                                      "balance": "368.00",
     *                                      "payment_date": "01.06.2022 10:35:59",
     *                                      "real_payment_date": "2022-06-01 10:35:59",
     *                                      "status": 0,
     *                                      "paid_at": null,
     *                                      "created_at": "2022-05-06T05:35:59.000000Z",
     *                                      "updated_at": "2022-05-06T05:35:59.000000Z"
     *                                  },
     *                                  {
     *                                      "id": 719347,
     *                                      "user_id": 371879,
     *                                      "contract_id": 64634,
     *                                      "price": "266.67",
     *                                      "total": "368.00",
     *                                      "balance": "368.00",
     *                                      "payment_date": "01.07.2022 10:35:59",
     *                                      "real_payment_date": "2022-07-01 10:35:59",
     *                                      "status": 0,
     *                                      "paid_at": null,
     *                                      "created_at": "2022-05-06T05:35:59.000000Z",
     *                                      "updated_at": "2022-05-06T05:35:59.000000Z"
     *                                  }
     *                              },
     *                              "created_at": "06.05.2022 10:35:59",
     *                              "manager_id": null
     *                          }
     *                      }
     *                  }
     *              )
     *          )
     *       ),
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
    public function contractsNotifications() {
        return $this->service::contracts();
    }

    /**
     * @OA\Get(
     *      path="/buyer/bonus-balance",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Бонусный баланс (Bonus balance)",
     *      description="Return json",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={"code":1,"error":{},"data":{"bonus":1000}})
     *          )
     *       ),
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
    public function bonusBalance(Request $request)
    {
        return $this->service::bonusBalance($request);
    }

    /**
     * @OA\Get(
     *      path="/buyer/pay-services/list",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Список платежных услуг (list of pay services)",
     *      description="Return json",
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={"code":1,"error":{},"data":{{"title":"Other","items":{{"id":6,"title":"Sarkor Telekom","type":"other","img":"http:\/\/test.loc:8888\/images\/partners\/sarkor.png"},{"id":7,"title":"TPS","type":"other","img":"http:\/\/test.loc:8888\/images\/partners\/tps.png"},{"id":8,"title":"UzOnline","type":"other","img":"http:\/\/test.loc:8888\/images\/partners\/uzmobile.png"},{"id":9,"title":"EVO","type":"other","img":"http:\/\/test.loc:8888\/images\/partners\/evo.png"},{"id":10,"title":"Comnet","type":"other","img":"http:\/\/test.loc:8888\/images\/partners\/comnet.png"},{"id":11,"title":"FiberNet","type":"other","img":"http:\/\/test.loc:8888\/images\/partners\/fibernet.png"},{"id":12,"title":"ISTV Internet","type":"other","img":"http:\/\/test.loc:8888\/images\/partners\/istvinternet.png"},{"id":13,"title":"FreeLink","type":"other","img":"http:\/\/test.loc:8888\/images\/partners\/freelink.png"},{"id":14,"title":"Sharq Telekom","type":"other","img":"http:\/\/test.loc:8888\/images\/partners\/st.png"},{"id":15,"title":"Buzton Internet","type":"other","img":"http:\/\/test.loc:8888\/images\/partners\/buztoninternet.png"},{"id":16,"title":"Airnet Internet","type":"other","img":"http:\/\/test.loc:8888\/images\/partners\/airnet_inet.png"},{"id":17,"title":"AllNet","type":"other","img":"http:\/\/test.loc:8888\/images\/partners\/all_net.png"},{"id":18,"title":"DGT","type":"other","img":"http:\/\/test.loc:8888\/images\/partners\/dgt.png"},{"id":19,"title":"East Stark TV","type":"other","img":"http:\/\/test.loc:8888\/images\/partners\/eaststark.png"},{"id":20,"title":"Nano Telecom","type":"other","img":"http:\/\/test.loc:8888\/images\/partners\/nanotelecom.png"},{"id":22,"title":"SkyLine","type":"other","img":"http:\/\/test.loc:8888\/images\/partners\/skyline.png"},{"id":23,"title":"Sola Wifi","type":"other","img":"http:\/\/test.loc:8888\/images\/partners\/sola_wifi.png"},{"id":24,"title":"Sonet","type":"other","img":"http:\/\/test.loc:8888\/images\/partners\/sonet.png"},{"id":25,"title":"Spectr Internet","type":"other","img":"http:\/\/test.loc:8888\/images\/partners\/spectrinternet.png"},{"id":26,"title":"Scientific technologies","type":"other","img":"http:\/\/test.loc:8888\/images\/partners\/uzscinet1.png"}},"type":1},{"title":"Mobile","items":{{"id":1,"title":"Beeline","type":"mobile","img":"http:\/\/test.loc:8888\/images\/partners\/beeline.png"},{"id":2,"title":"Mobiuz (UMS)","type":"mobile","img":"http:\/\/test.loc:8888\/images\/partners\/Mobiuz.png"},{"id":3,"title":"Perfectum","type":"mobile","img":"http:\/\/test.loc:8888\/images\/partners\/perfectum_n.png"},{"id":4,"title":"Ucell","type":"mobile","img":"http:\/\/test.loc:8888\/images\/partners\/uceluz.png"},{"id":5,"title":"UzMobile","type":"mobile","img":"http:\/\/test.loc:8888\/images\/partners\/uzmobile.png"}},"type":0}}})
     *          )
     *       ),
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
    public function payServices(Request $request)
    {
        return $this->service::payServices($request);
    }

    /**
     * @OA\Post(
     *      path="/buyer/pay-services/pay",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод оплата за выбранные платежные услуги. (Payment for selected pay services) (В том числе оплата с бонусных сумм)",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="amount",
     *          description="Amount",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="account",
     *          description="Account phone number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="id",
     *          description="ID of service (tps,beeline)",
     *          required=true,
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
     *              @OA\Schema(example={"code":1,"error":{},"data":{}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"code":0,"error":{{"type":"danger","text":"To'lovni amalga oshirish uchun bonus ball yetarli emas"}},"data":{}}
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
    public function payServicePayment(Request $request)
    {
        return $this->service::payServicePayment($request);
    }

    /**
     * @OA\Post(
     *      path="/buyer/deposit/add",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод добавление баланс личного счета для покупателя (Add personal account balance for buyer)",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="sum",
     *          description="Amount",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="card_id",
     *          description="Card id",
     *          required=true,
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
     *              @OA\Schema(example={"code":1,"error":{},"data":{}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"code":0,"error":{{"type":"danger","text":"app.card_not_found"}},"data":{}}
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
    public function addDeposit(Request $request)
    {
        return $this->service::addDeposit($request);
    }

    /**
     * @OA\Post(
     *      path="/buyer/bonus-to-card",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод перевод бонусных сумм на карту продавца (Bonus transfer to seller’s card)",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="bonus_sum_request",
     *          description="Bonus sum",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="card_id",
     *          description="Card id",
     *          required=true,
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
     *              @OA\Schema(example={"code":1,"error":{},"data":{}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"code":0,"error":{{"type":"danger","text":"Kartaga o'tkazish uchun bonuslar yetarli emas (shu jumladan komissiya uchun 1%)"}},"data":{}}
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
    public function bonusToCard(Request $request)
    {
        return $this->service::bonusToCard($request);
    }

    /**
     * @OA\Post(
     *      path="/buyer/bonus-to-card-confirm",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="(CONFIRM) Метод перевод бонусных сумм на карту продавца (Bonus transfer to seller’s card)",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="amount",
     *          description="Amount",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="card_id",
     *          description="Card id",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="sms_code",
     *          description="OTP",
     *          required=true,
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
     *              @OA\Schema(example={"code":1,"error":{},"data":{}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"code":0,"error":{{"type":"danger","text":"SMS kod xato kiritilgan"}},"data":{}}
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
    public function bonusToCardConfirm(Request $request)
    {
        return $this->service::bonusToCardConfirm($request);
    }

    /**
     * @OA\Get(
     *      path="/buyer/catalog/partners/list",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Список партнеров по категориям (list partners of categories)",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="catalog_id",
     *          description="ID of specific catalog",
     *          required=true,
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
     *                  "status": "success",
     *                  "error": {},
     *                  "data": {
     *                      {
     *                          "title": "OK «RAJAB RAHMON»",
     *                          "img": "https://newres.test.uz/storage//company/215209/9b3b01db464cf3f113c1a3c2301984eb.png",
     *                          "id": 215209
     *                      },
     *                      {
     *                          "title": "OOO «MEDIAPARK GROUP»",
     *                          "img": "",
     *                          "id": 215339
     *                      },
     *                      {
     *                          "title": "OOO «MEDIAPARK GROUP»",
     *                          "img": "",
     *                          "id": 215340
     *                      },
     *                      {
     *                          "title": "OOO «MEDIAPARK GROUP»",
     *                          "img": "",
     *                          "id": 215352
     *                      }
     *                  }
     *              })
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request or error messages",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"code":0,"error":{{"type":"danger","text":"app.catalog_id_not_fill"}},"data":{}}
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
    public function catalogPartners(Request $request)
    {
        return $this->service::catalogPartners($request);
    }

    /**
     * @OA\Post(
     *      path="/buyer/catalog-partner",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Информация о партнере (partner information",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="partner_id",
     *          description="ID of specific partner",
     *          required=true,
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
     *                  "status": "success",
     *                  "error": {},
     *                  "data": {
     *                      {
     *                          "fillial_id": 215340,
     *                          "title": null,
     *                          "address": "г.Ташкент, Шайхантахурский р-н,ул. Караташ, д.11-А",
     *                          "img": "",
     *                          "phone": "998712033333"
     *                      },
     *                      {
     *                          "fillial_id": 215351,
     *                          "title": null,
     *                          "address": "г.Ташкент Чиланзарский р-н ул. Ц квартал Катартал дом 28",
     *                          "img": "",
     *                          "phone": "998712033333"
     *                      },
     *                      {
     *                          "fillial_id": 215352,
     *                          "title": null,
     *                          "address": "г.Ташкент ул.Фархадская дом 31-а",
     *                          "img": "",
     *                          "phone": "998712033333"
     *                      }
     *                  }
     *              })
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request or error messages",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"code":0,"error":{{"type":"danger","text":"Not found"}},"data":{}}
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
    public function catalogPartner(Request $request)
    {
        return $this->service::catalogPartner($request);
    }

    public function checkContract($id)
    {
        $contract = Contract::select('contracts.id', 'orders.*')
            ->leftJoin('orders', 'orders.id', '=', 'contracts.order_id')
            ->where('contracts.id', '=', $id)
            ->first();

        if ($contract) {
            return $this->handleResponse($contract);
        }

        return $this->service::handleError([]);
    }

    public function expiredContractsAutopay(Request $request)
    {
        return $this->service::expiredContractsAutopay($request);
    }

    public function uploadAddress(UploadAddressRequest $request)
    {
        return $this->service->uploadAddress($request);
    }

    public function limits() {
        $response = $this->service->getBuyerLimits();
        $this->service->handleResponse($response);
    }
}
