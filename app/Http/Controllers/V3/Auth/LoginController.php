<?php

namespace App\Http\Controllers\V3\Auth;

use App\Http\Controllers\V3\CoreController;
use App\Services\API\V3\LoginService;
use Illuminate\Http\Request;

class LoginController extends CoreController
{
    protected LoginService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new LoginService();
    }

    /**
     * @OA\Post(
     *      path="/login/send-sms-code",
     *      tags={"Authorization"},
     *      summary="Метод отправка четырехзначного кода смс на номер телефона для авторизации (SMS code user by phone)",
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
        $inputs = $this->service::validateSendSmsCode($request);
        return $this->service::sendSmsCode($inputs['phone']);
    }

    /**
     * @OA\Post(
     *      path="/login/auth",
     *      tags={"Authorization"},
     *      summary="Метод подтверждение четырехзначного кода смс для авторизации (Authorization user by phone or id and password)",
     *      description="Return json",
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
     *                      "api_token": "dc8f54ff0591f628c29c9bc669bf5844"
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
    public function auth(Request $request)
    {
        return $this->service::auth($request);
    }


    /**
     * @OA\Post(
     *      path="/me",
     *      tags={"Authorization"},
     *      summary="Метод получения личной информации (Me)",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={
     *                  "status": "success",
     *                  "error": {},
     *                  "data": {
     *                      "id": 372992,
     *                      "email": null,
     *                      "name": "Шахзод",
     *                      "surname": "Машраббоев",
     *                      "patronymic": "Абдуллаевич",
     *                      "gender": null,
     *                      "phone": "+998900430457",
     *                      "birth_date": null,
     *                      "region": null,
     *                      "local_region": null,
     *                      "email_verified_at": null,
     *                      "token_generated_at": "2022-07-15 13:54:57",
     *                      "verify_message": null,
     *                      "status": 0,
     *                      "status_employee": 1,
     *                      "company_id": null,
     *                      "seller_company_id": null,
     *                      "created_at": "2022-07-15T08:54:57.000000Z",
     *                      "created_by": null,
     *                      "updated_at": "2022-07-15T08:55:12.000000Z",
     *                      "verified_at": null,
     *                      "verified_by": null,
     *                      "ticketit_admin": 0,
     *                      "ticketit_agent": 0,
     *                      "kyc_status": 0,
     *                      "kyc_id": null,
     *                      "is_saller": 0,
     *                      "device_os": null,
     *                      "lang": null,
     *                      "firebase_token_android": null,
     *                      "firebase_token_ios": null,
     *                      "doc_path": 1,
     *                      "black_list": 0,
     *                      "vip": 0,
     *                      "role_id": 12
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
    public function me(Request $request)
    {
        return $this->service::me($request);
    }

    /**
     * @OA\Post(
     *      path="/logout",
     *      tags={"Authorization"},
     *      summary="Метод выхода из системы (Logout)",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
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
    public function logout(Request $request)
    {
        return $this->service::logout($request);
    }
}
