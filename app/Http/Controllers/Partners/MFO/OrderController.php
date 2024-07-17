<?php

namespace App\Http\Controllers\Partners\MFO;

use App\Http\Controllers\V3\CoreController;
use App\Http\Requests\V3\MFO\CreateOrderV3MFORequest;
use App\Http\Requests\V3\MFO\OrderCalculateV3MFORequest;
use App\Models\Contract;
use App\Services\API\V3\BaseService;
use App\Services\MFO\MFOOrderService;
use App\Services\MFO\MFOPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderController extends CoreController
{
    private MFOOrderService $mfoOrderService;

    public function __construct()
    {
        parent::__construct();
        $this->mfoOrderService = new MFOOrderService;
    }

    public function validateCheckContractStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id',
        ]);
        if ($validator->fails()) {
            BaseService::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public function validateCheckCancelContractSmsCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id',
            'code' => 'required|numeric|digits:4',
        ]);
        if ($validator->fails()) {
            return BaseService::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public function validateMyIdRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|integer',
            'passport_selfie'       => 'required|image|mimes:bmp,jpe,jpg,jpeg,png,webp|max:35840',
        ]);
        if ($validator->fails()) {
            return BaseService::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public function validateSignContract(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|integer',
            'sign' => 'required|file'
        ]);
        if ($validator->fails()) {
            return BaseService::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    /**
     * @OA\Post(
     *      path="/mfo/calculate",
     *      tags={"MFO"},
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
     *                      type="string"
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
    public function calculate(OrderCalculateV3MFORequest $request)
    {
        $response = $this->mfoOrderService->calculate($request);
        return BaseService::handleResponse($response);
    }

    /**
     * @OA\Post(
     *      path="mfo/order",
     *      tags={"MFO"},
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
    public function createOrder(CreateOrderV3MFORequest $request)
    {
        $response = $this->mfoOrderService->createOrder($request);
        return BaseService::handleResponse($response);
    }

    /**
     * @OA\Post(
     *      path="/mfo/check-status",
     *      tags={"MFO"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод возвращает данные об саб статусах контракта",
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
     *              @OA\Schema(example={"status": "success","error":{},"data":{"id": "1", "contract_id": 3, "statuses": 30}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(example={"status": "error","error":{{"type":"danger","text":"contract not found"}},"data":{}})
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
    public function checkContractStatus(Request $request)
    {
        $validated = $this->validateCheckContractStatus($request);
        $response = $this->mfoOrderService->checkContractStatus($validated['contract_id']);
        return BaseService::handleResponse($response);
    }

    /**
     * @OA\Post(
     *      path="/mfo/myid",
     *      tags={"MFO"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод Верификации пользователя через систему myid, с помощью селфи (через сдк)",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="passport_selfie",
     *          description="Passport selfie photo",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="file"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="contract_id",
     *          description="Contact Id",
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
     *              @OA\Schema(example={"status": "success","error":{},"data":{"contract_id":64634,"message":"Shartnoma muvaffaqiyatli tasdiqlandi! "}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(example={"status": "error","error":{{"type":"danger","text":"wrong sms code"}},"data":{}})
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
    public function myid(Request $request)
    {
        $this->validateMyIdRequest($request);
        $this->mfoOrderService->myIdVerify($request['passport_selfie'],$request['contract_id']);
        return BaseService::handleResponse();
    }

    public function signContract(Request $request)
    {
        $validated = $this->validateSignContract($request);
        $link =  $this->mfoOrderService->signContract($validated['contract_id'],$validated['sign'],$request['language'] ?? 'ru');
        return BaseService::handleResponse(['link' => $link]);
    }

    /**
     * @OA\Post(
     *      path="/mfo/cancel-contract/send",
     *      tags={"MFO"},
     *      security={{"api_token_security":{}}},
     *      summary="Cancel contract MFO",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="contract_id",
     *          description="Contract ID",
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
     *              @OA\Schema(example={"status":"success","error":{},"data":{}})
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
     *                      "error": {
     *                          {
     *                              "type": "danger",
     *                              "text": "Договор не найден!"
     *                          }
     *                      },
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
    public function cancelContractMFOSendSms(Request $request)
    {
        $inputs = $this->validateCheckContractStatus($request);
        $mfo_service = new MFOPaymentService();
        $contract = Contract::find($inputs['contract_id']);
        $result = $mfo_service->cancelTransactionSendSms($contract);
        if($result['status'] == 'error'){
            BaseService::handleError([$result['message']]);
        }
        BaseService::handleResponse([$result['data']]);
    }

    /**
     * @OA\Post(
     *      path="/mfo/cancel-contract/check",
     *      tags={"MFO"},
     *      security={{"api_token_security":{}}},
     *      summary="Cancel contract MFO",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="contract_id",
     *          description="Contract ID",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="code",
     *          description="SMS code",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={"status":"success","error":{},"data":{}})
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
     *                      "error": {
     *                          {
     *                              "type": "danger",
     *                              "text": "Договор не найден!"
     *                          }
     *                      },
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
    public function cancelContractMFOCheckSms(Request $request)
    {
        $validated = $this->validateCheckCancelContractSmsCode($request);
        $mfo_service = new MFOPaymentService();
        $contract = Contract::find($validated['contract_id']);
        $result = $mfo_service->cancelTransactionCheckSms($contract,$validated['code'],$validated['hashedCode'] ?? null);
        if($result['status'] == 'error'){
            BaseService::handleError([$result['message']]);
        }
        BaseService::handleResponse([$result['message']]);
    }

}
