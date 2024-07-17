<?php

namespace App\Http\Controllers\Partners;

use App\Http\Controllers\V3\CoreController;
use App\Http\Requests\V3\UploadPassportDocsRequest;
use App\Services\API\V3\LoginService;
use App\Services\API\V3\Partners\PartnerBuyerService;
use Illuminate\Http\Request;

class PartnerBuyerController extends CoreController
{
    protected PartnerBuyerService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new PartnerBuyerService();
    }

    /**
     * @OA\Post(
     *      path="/partner/buyers/send-sms-code",
     *      tags={"Authorization"},
     *      summary="Метод отправка четырехзначного кода смс на номер телефона для авторизации (SMS code user by phone)",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *      @OA\Parameter(
     *          name="phone",
     *          description="Phone",
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
     *              @OA\Schema(example={
     *                  "status": "success",
     *                  "error": {},
     *                  "data": {
     *                      "hashed": "$2y$10$Tk.3qq026oI9gPRMmEj9Puh4ujc7R0uQB5SeHzBN.freF.U0z8GTu"
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
     *                     example={
     *                          "status": "error",
     *                          "error": {
     *                              {
     *                                  "type": "danger",
     *                                  "text": "Длина цифрового поля Telefon должна быть 12."
     *                              }
     *                          },
     *                          "data": {}
     *                      }
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
    public function sendSmsCode(Request $request)
    {
        $this->service->validatePhone($request);
        return LoginService::sendSmsCode($request->phone);
    }
    /**
     * @OA\Post(
     *      path="/partner/buyers/check-sms-code",
     *      tags={"Authorization"},
     *      summary="Метод подтверждение четырехзначного кода смс для авторизации (Authorization user by phone or id and password)",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *      @OA\Parameter(
     *          name="phone",
     *          description="Phone",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
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
     *              @OA\Schema(example={
     *                  "status": "success",
     *                  "error": {},
     *                  "data": {
     *                      "user_status": 2,
     *                      "user_id": 372390,
     *                      "api_token": "dc8f54ff0591f628c29c9bc669bf5844",
     *                      "is_seller": null
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
     *                     example={
     *                      "status": "error",
     *                      "error": {
     *                          {
     *                              "type": "danger",
     *                              "text": "SMS kodi noto`g`ri "
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
    public function checkSmsCode(Request $request)
    {
        return $this->service->checkSmsCode($request);
    }

    public function validateForm(Request $request)
    {
        $result = $this->service->validateForm($request);
        return $result;
    }

    /**
     * @OA\Post(
     *      path="/partner/buyers/check-vip",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Проверка статус випа ",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="phone",
     *          description="Phone",
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
     *              @OA\Schema(example={
     *                  "status": "success",
     *                  "error": {},
     *                  "data": {
     *                      "vip": 0
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
     *                     example={
     *                          "status": "error",
     *                          "error": {
     *                              {
     *                                  "type": "danger",
     *                                  "text": "Длина цифрового поля Telefon должна быть 12."
     *                              }
     *                          },
     *                          "data": {}
     *                      }
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
    public function checkVip(Request $request)
    {
        $this->service->validatePhone($request);
        return $this->service->checkVip($request);
    }

    /**
     * @OA\Post(
     *      path="/partner/buyer/add-guarant",
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
     *                  @OA\Property(
     *                      property="buyer_id",
     *                      type="integer"
     *                  ),
     *                  example={"buyer_id":372708,"data":{{"phone":"998900626204","name":"Guarant first"},{"phone":"998974708222","name":"Guarant second"}}}
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
        $this->service->validateAddGuarant($request);
        return $this->service->addGuarant($request);
    }

    public function getPartnerDetailInformation()
    {
      $this->service->getPartnerDetailInformation();
    }

    /**
    * @OA\Post(
    *      path="/partner/buyer/upload-passport-docs",
    *      tags={"Buyer"},
    *      security={{"api_token_security":{}}},
    *      summary="Загрузка фото паспорта и ID карты.",
    *      description="Return json",
    *      @OA\Parameter(
    *          name="buyer_id",
    *          description="ID клиента в системе test",
    *          required=true,
    *          in="query",
    *          @OA\Schema(
    *              type="integer"
    *          )
    *      ),
    *      @OA\Parameter(
    *          name="passport_type",
    *          description="Тип паспорта : 6 = Паспорт; 0 = ID карта",
    *          required=true,
    *          in="query",
    *          @OA\Schema(
    *              type="integer"
    *          )
    *      ),
    *      @OA\Parameter(
    *          name="passport_selfie",
    *          description="Фотография селфи клиента с папортом",
    *          required=false,
    *          in="query",
    *          @OA\Schema(
    *              type="file"
    *          )
    *      ),
    *      @OA\Parameter(
    *          name="passport_first_page",
    *          description="Первая страница паспорта",
    *          required=false,
    *          in="query",
    *          @OA\Schema(
    *              type="file"
    *          )
    *      ),
    *      @OA\Parameter(
    *          name="passport_with_address",
    *          description="Страница прописки",
    *          required=false,
    *          in="query",
    *          @OA\Schema(
    *              type="file"
    *          )
    *      ),
    *      @OA\Parameter(
    *          name="id_selfie",
    *          description="Фотография селфи клиента с ID",
    *          required=false,
    *          in="query",
    *          @OA\Schema(
    *              type="file"
    *          )
    *      ),
    *      @OA\Parameter(
    *          name="id_first_page",
    *          description="Передняя сторона ID карты",
    *          required=false,
    *          in="query",
    *          @OA\Schema(
    *              type="file"
    *          )
    *      ),
    *      @OA\Parameter(
    *          name="id_second_page",
    *          description="Задняя сторона ID карты",
    *          required=false,
    *          in="query",
    *          @OA\Schema(
    *              type="file"
    *          )
    *      ),
    *      @OA\Parameter(
    *          name="id_with_address",
    *          description="Страница прописки",
    *          required=false,
    *          in="query",
    *          @OA\Schema(
    *              type="file"
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
    *                  "data": {}
    *              })
    *          )
    *       ),
    *      @OA\Response(
    *          response=400,
    *          description="Bad Request",
    *          @OA\MediaType(
    *              mediaType="application/json",
    *                 @OA\Schema(
    *                     example={
    *                          "status": "error",
    *                          "error": {
    *                              {
    *                                  "type": "danger",
    *                                  "text": "Поле buyer id обязательно для заполнения"
    *                              }
    *                          },
    *                          "data": {}
    *                      }
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
    public function uploadPassportDocs(UploadPassportDocsRequest $request)
    {
        return $this->service->uploadPassportDocs($request);
    }

    /**
     * @OA\Post(
     *      path="/partner/buyer/check_status",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Проверить статус покупателя",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="phone",
     *          description="Телефон номер клиента в системе test",
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
     *              @OA\Schema(example={"status":"success","error":{},"data":{"status":12,"buyer_id":1,"passport_type":6,"address_is_received":0,"available_periods":{"3":16,"6":26,"9":34,"12":38}}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                          "status": "error",
     *                          "error": {
     *                              {
     *                                  "type": "danger",
     *                                  "text": "Покупатель не найден!"
     *                              }
     *                          },
     *                          "data": {}
     *                      }
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
    public function checkBuyerStatus(Request $request)
    {
        $this->service->validatePhone($request);
        return $this->service->checkBuyerStatus($request);
    }
}
