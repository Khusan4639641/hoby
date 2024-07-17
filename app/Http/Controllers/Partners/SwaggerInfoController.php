<?php

namespace App\Http\Controllers\Partners;

use App\Http\Controllers\Controller;

class SwaggerInfoController extends Controller
{
    /**
     * @OA\Info(
     *      version="3.0.0",
     *      title="REST API test Partners Documentation",
     *      description="Documentation for developoers,structure,request,response etc..",
     *      @OA\Contact(
     *          email="info@test.xyz"
     *      ),
     * )
     *
     * @OA\Server(
     *      url=L5_SWAGGER_CONST_HOST2,
     *      description="REST API test Partners Service V3"
     * )
     *
     * @OA\Tag(
     *     name="test",
     *     description="REST API test Partners",
     *     name="Authorization",
     *     description="Authorization buyer, partner and employeer. For buyer first use method send-sms-code after receive SMS use method auth. For employeer use phone and password in method auth. For partner use partner_id and password"
     * )
     */

    //Categories list
    /**
     * @OA\Get(
     *      path="/categories/tree/list",
     *      tags={"Categories"},
     *      security={{"api_token_security":{}}},
     *      summary="Лист home пейджа",
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
     *                          "id": 1245,
     *                          "sort": 500,
     *                          "parent_id": 0,
     *                          "created_at": "2022-07-07T07:37:53.000000Z",
     *                          "updated_at": "2022-07-07T07:37:53.000000Z",
     *                          "psic_code": "08528001001000000",
     *                          "psic_text": "Телевизор ЭЛЕКТРИЧЕСКИЕ МАШИНЫ И ОБОРУДОВАНИЕ, ИХ ЧАСТИ\n Телевизоры и мониторы",
     *                          "marketplace_id": 353,
     *                          "status": 1,
     *                          "child": {
     *                              {
     *                                  "id": 1246,
     *                                  "sort": 500,
     *                                  "parent_id": 1245,
     *                                  "created_at": "2022-07-07T07:37:53.000000Z",
     *                                  "updated_at": "2022-07-08T07:59:31.000000Z",
     *                                  "psic_code": "08528001001000000",
     *                                  "psic_text": "Телевизор ЭЛЕКТРИЧЕСКИЕ МАШИНЫ И ОБОРУДОВАНИЕ, ИХ ЧАСТИ\n Телевизоры и мониторы",
     *                                  "marketplace_id": 354,
     *                                  "status": 1,
     *                                  "child": {},
     *                                  "language": {
     *                                      "id": 2835,
     *                                      "language_code": "ru",
     *                                      "category_id": 1246,
     *                                      "title": "Телевизоры",
     *                                      "slug": "1246",
     *                                      "preview_text": "",
     *                                      "detail_text": "",
     *                                      "created_at": "2022-07-07T07:37:53.000000Z",
     *                                      "updated_at": "2022-07-07T07:37:53.000000Z"
     *                                  }
     *                              },
     *                              {
     *                                  "id": 1332,
     *                                  "sort": 500,
     *                                  "parent_id": 1245,
     *                                  "created_at": "2022-07-07T07:37:55.000000Z",
     *                                  "updated_at": "2022-07-08T07:59:32.000000Z",
     *                                  "psic_code": "08528005001000000",
     *                                  "psic_text": "Тюнер для телевизора ЭЛЕКТРИЧЕСКИЕ МАШИНЫ И ОБОРУДОВАНИЕ, ИХ ЧАСТИ\n Телевизоры и мониторы",
     *                                  "marketplace_id": 402,
     *                                  "status": 1,
     *                                  "child": {
     *                                      {
     *                                          "id": 1283,
     *                                          "sort": 500,
     *                                          "parent_id": 1332,
     *                                          "created_at": "2022-07-07T07:37:54.000000Z",
     *                                          "updated_at": "2022-07-08T07:59:32.000000Z",
     *                                          "psic_code": "08528005002000000",
     *                                          "psic_text": "ТВ приставка ЭЛЕКТРИЧЕСКИЕ МАШИНЫ И ОБОРУДОВАНИЕ, ИХ ЧАСТИ\n Телевизоры и мониторы",
     *                                          "marketplace_id": 404,
     *                                          "status": 1,
     *                                          "child": {},
     *                                          "language": {
     *                                              "id": 2909,
     *                                              "language_code": "ru",
     *                                              "category_id": 1283,
     *                                              "title": "ТВ-приставки",
     *                                              "slug": "1283",
     *                                              "preview_text": "",
     *                                              "detail_text": "",
     *                                              "created_at": "2022-07-07T07:37:54.000000Z",
     *                                              "updated_at": "2022-07-07T07:37:54.000000Z"
     *                                          }
     *                                      },
     *                                      {
     *                                          "id": 1316,
     *                                          "sort": 500,
     *                                          "parent_id": 1332,
     *                                          "created_at": "2022-07-07T07:37:55.000000Z",
     *                                          "updated_at": "2022-07-08T07:59:32.000000Z",
     *                                          "psic_code": "08517007009000000",
     *                                          "psic_text": "Медиаплеер ЭЛЕКТРИЧЕСКИЕ МАШИНЫ И ОБОРУДОВАНИЕ, ИХ ЧАСТИ\n Телефоны",
     *                                          "marketplace_id": 405,
     *                                          "status": 1,
     *                                          "child": {},
     *                                          "language": {
     *                                              "id": 2975,
     *                                              "language_code": "ru",
     *                                              "category_id": 1316,
     *                                              "title": "Медиаплееры-ресиверы",
     *                                              "slug": "1316",
     *                                              "preview_text": "",
     *                                              "detail_text": "",
     *                                              "created_at": "2022-07-07T07:37:55.000000Z",
     *                                              "updated_at": "2022-07-07T07:37:55.000000Z"
     *                                          }
     *                                      }
     *                                  },
     *                                  "language": {
     *                                      "id": 3007,
     *                                      "language_code": "ru",
     *                                      "category_id": 1332,
     *                                      "title": "ТВ-приставки и медиаплееры",
     *                                      "slug": "1332",
     *                                      "preview_text": "",
     *                                      "detail_text": "",
     *                                      "created_at": "2022-07-07T07:37:55.000000Z",
     *                                      "updated_at": "2022-07-07T07:37:55.000000Z"
     *                                  }
     *                              },
     *                              {
     *                                  "id": 1341,
     *                                  "sort": 500,
     *                                  "parent_id": 1245,
     *                                  "created_at": "2022-07-07T07:37:55.000000Z",
     *                                  "updated_at": "2022-07-08T07:59:33.000000Z",
     *                                  "psic_code": "09403001009000000",
     *                                  "psic_text": "Подставка для телевизора МЕБЕЛЬ, КРОВАТИ И СВЕТИЛЬНИКИ Мебель прочая и ее части",
     *                                  "marketplace_id": 361,
     *                                  "status": 1,
     *                                  "child": {
     *                                      {
     *                                          "id": 1342,
     *                                          "sort": 500,
     *                                          "parent_id": 1341,
     *                                          "created_at": "2022-07-07T07:37:55.000000Z",
     *                                          "updated_at": "2022-07-08T07:59:33.000000Z",
     *                                          "psic_code": "09403001009000000",
     *                                          "psic_text": "Подставка для телевизора МЕБЕЛЬ, КРОВАТИ И СВЕТИЛЬНИКИ Мебель прочая и ее части",
     *                                          "marketplace_id": 362,
     *                                          "status": 1,
     *                                          "child": {},
     *                                          "language": {
     *                                              "id": 3027,
     *                                              "language_code": "ru",
     *                                              "category_id": 1342,
     *                                              "title": "Настенное крепление",
     *                                              "slug": "1342",
     *                                              "preview_text": "",
     *                                              "detail_text": "",
     *                                              "created_at": "2022-07-07T07:37:55.000000Z",
     *                                              "updated_at": "2022-07-07T07:37:55.000000Z"
     *                                          }
     *                                      },
     *                                      {
     *                                          "id": 1343,
     *                                          "sort": 500,
     *                                          "parent_id": 1341,
     *                                          "created_at": "2022-07-07T07:37:55.000000Z",
     *                                          "updated_at": "2022-07-08T07:59:33.000000Z",
     *                                          "psic_code": "09403001009000000",
     *                                          "psic_text": "Подставка для телевизора МЕБЕЛЬ, КРОВАТИ И СВЕТИЛЬНИКИ Мебель прочая и ее части",
     *                                          "marketplace_id": 363,
     *                                          "status": 1,
     *                                          "child": {},
     *                                          "language": {
     *                                              "id": 3029,
     *                                              "language_code": "ru",
     *                                              "category_id": 1343,
     *                                              "title": "Потолочное крепление",
     *                                              "slug": "1343",
     *                                              "preview_text": "",
     *                                              "detail_text": "",
     *                                              "created_at": "2022-07-07T07:37:55.000000Z",
     *                                              "updated_at": "2022-07-07T07:37:55.000000Z"
     *                                          }
     *                                      },
     *                                      {
     *                                          "id": 2010,
     *                                          "sort": 500,
     *                                          "parent_id": 1341,
     *                                          "created_at": "2022-07-07T07:38:18.000000Z",
     *                                          "updated_at": "2022-07-08T07:59:43.000000Z",
     *                                          "psic_code": "09403001009000000",
     *                                          "psic_text": "Подставка для телевизора МЕБЕЛЬ, КРОВАТИ И СВЕТИЛЬНИКИ Мебель прочая и ее части",
     *                                          "marketplace_id": 1127,
     *                                          "status": 1,
     *                                          "child": {},
     *                                          "language": {
     *                                              "id": 4363,
     *                                              "language_code": "ru",
     *                                              "category_id": 2010,
     *                                              "title": "Настольное крепление",
     *                                              "slug": "2010",
     *                                              "preview_text": "",
     *                                              "detail_text": "",
     *                                              "created_at": "2022-07-07T07:38:18.000000Z",
     *                                              "updated_at": "2022-07-07T07:38:18.000000Z"
     *                                          }
     *                                      }
     *                                  },
     *                                  "language": {
     *                                      "id": 3025,
     *                                      "language_code": "ru",
     *                                      "category_id": 1341,
     *                                      "title": "Кронштейны и подставки",
     *                                      "slug": "1341",
     *                                      "preview_text": "",
     *                                      "detail_text": "",
     *                                      "created_at": "2022-07-07T07:37:55.000000Z",
     *                                      "updated_at": "2022-07-07T07:37:55.000000Z"
     *                                  }
     *                              }
     *                          },
     *                          "language": {
     *                              "id": 2833,
     *                              "language_code": "ru",
     *                              "category_id": 1245,
     *                              "title": "Телевизоры и видеотехника",
     *                              "slug": "1245",
     *                              "preview_text": "",
     *                              "detail_text": "",
     *                              "created_at": "2022-07-07T07:37:53.000000Z",
     *                              "updated_at": "2022-07-07T07:37:53.000000Z"
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

    //buyer/send-sms-code-uz
    /**
     * @OA\Post(
     *      path="/buyer/send-sms-code-uz",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод отправка четырехзначного кода смс на номер телефона для добавление карты платежей (Send SMS code for verification)",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="card",
     *          description="Card number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="exp",
     *          description="Expiration date of card",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
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
     *              @OA\Schema(example={"status": "success","error":{},"data":{"hashed":"$2y$10$A2Hp0gSEOJIWCOgdMDNPvu71WVuJg4cYeXU8aEzlogIfaTp4NGeNu"}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                   example={"status": "error","error":{{"type":"danger","text":"Ushbu karta tizimga qo'shilib bo'lingan!"}},"data":{}}
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

    //buyer/check-sms-code-uz
    /**
     * @OA\Post(
     *      path="/buyer/check-sms-code-uz",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод подтверждение четырехзначного кода смс для добавление карты платежей (Send sms code for confirm verification)",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="card_number",
     *          description="Card number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="card_valid_date",
     *          description="Expiration date of card",
     *          required=true,
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
     *              @OA\Schema(example={"status": "success","error":{},"data":{}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={"status": "error","error":{{"type":"danger","text":"Invalid code"}},"data":{}})
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

    //myid/job
    /**
     * @OA\Post(
     *      path="/myid/job",
     *      tags={"MyID"},
     *      security={{"api_token_security":{}}},
     *      summary="MyID get credentials",
     *      description="Return json",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      description="Passport data",
     *                      property="pass_data",
     *                      type="string",
     *                 ),
     *                  @OA\Property(
     *                      description="Image",
     *                      property="passport_selfie",
     *                      type="file",
     *                 ),
     *                  @OA\Property(
     *                      description="Birthday",
     *                      property="birth_date",
     *                      type="string",
     *                 ),
     *                  @OA\Property(
     *                      description="Agreed on terms",
     *                      property="agreed_on_terms",
     *                      type="integer",
     *                 ),
     *                  @OA\Property(
     *                      description="Partner ID",
     *                      property="partner_id",
     *                      type="integer",
     *                 ),
     *                  required={"pass_data","passport_selfie","birth_date","agreed_on_terms"}
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
     *                  "data": {}
     *              })
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

    //contracts/sign
    /**
     * @OA\Post(
     *      path="/contracts/sign",
     *      tags={"Contracts"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод для онлайн подписи (buyer’s online signature)",
     *      description="Return json",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      description="Sign file",
     *                      property="sign",
     *                      type="file",
     *                 ),
     *                  @OA\Property(
     *                      description="Contract id",
     *                      property="id",
     *                      type="itenger",
     *                 ),
     *                  required={"sign","id"}
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={"status": "success","error":{},"data":{"link":"https://test.uz/storage/contract/63494/3d1d8739cca84e58a600fd8c94cc8450.html"}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"status": "error","error": {{"type": "danger","text": "Поле id обязательно для заполнения."}},"data": {}}
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

    //buyers/send-code-sms
    /**
     * @OA\Post(
     *      path="/buyers/send-code-sms",
     *      tags={"Buyers"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод отправка четырехзначного кода смс на номер телефона для активации контракта (SMS code contract for buyer)",
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
     *      @OA\Parameter(
     *          name="phone",
     *          description="Buyer phone",
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
     *              @OA\Schema(example={"status": "success","error":{},"data":{"hashed":"$2y$10$DKQl6vG4bwU0kw1z7OEDceOPuvhdUlMdYUeKhBODBLwAIk\/2uoE06"}})
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

    //buyers/check-code-sms
    /**
     * @OA\Post(
     *      path="/buyers/check-code-sms",
     *      tags={"Buyers"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод подтверждение четырехзначного кода смс для активации контракта (Contract confirm by buyer)",
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
     *          name="code",
     *          description="OTP Code",
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

    //Categories/detail/{id}
    /**
     * @OA\Get(
     *      path="/categories/detail/{id}",
     *      tags={"Categories"},
     *      security={{"api_token_security":{}}},
     *      summary="Показывает Item по выбранной категории",
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
     *                          "id": 1245,
     *                          "sort": 500,
     *                          "parent_id": 0,
     *                          "created_at": "2022-07-07T07:37:53.000000Z",
     *                          "updated_at": "2022-07-07T07:37:53.000000Z",
     *                          "psic_code": "08528001001000000",
     *                          "psic_text": "Телевизор ЭЛЕКТРИЧЕСКИЕ МАШИНЫ И ОБОРУДОВАНИЕ, ИХ ЧАСТИ\n Телевизоры и мониторы",
     *                          "marketplace_id": 353,
     *                          "status": 1,
     *                          "child": {
     *                              {
     *                                  "id": 1246,
     *                                  "sort": 500,
     *                                  "parent_id": 1245,
     *                                  "created_at": "2022-07-07T07:37:53.000000Z",
     *                                  "updated_at": "2022-07-08T07:59:31.000000Z",
     *                                  "psic_code": "08528001001000000",
     *                                  "psic_text": "Телевизор ЭЛЕКТРИЧЕСКИЕ МАШИНЫ И ОБОРУДОВАНИЕ, ИХ ЧАСТИ\n Телевизоры и мониторы",
     *                                  "marketplace_id": 354,
     *                                  "status": 1,
     *                                  "child": {},
     *                                  "language": {
     *                                      "id": 2835,
     *                                      "language_code": "ru",
     *                                      "category_id": 1246,
     *                                      "title": "Телевизоры",
     *                                      "slug": "1246",
     *                                      "preview_text": "",
     *                                      "detail_text": "",
     *                                      "created_at": "2022-07-07T07:37:53.000000Z",
     *                                      "updated_at": "2022-07-07T07:37:53.000000Z"
     *                                  }
     *                              },
     *                              {
     *                                  "id": 1332,
     *                                  "sort": 500,
     *                                  "parent_id": 1245,
     *                                  "created_at": "2022-07-07T07:37:55.000000Z",
     *                                  "updated_at": "2022-07-08T07:59:32.000000Z",
     *                                  "psic_code": "08528005001000000",
     *                                  "psic_text": "Тюнер для телевизора ЭЛЕКТРИЧЕСКИЕ МАШИНЫ И ОБОРУДОВАНИЕ, ИХ ЧАСТИ\n Телевизоры и мониторы",
     *                                  "marketplace_id": 402,
     *                                  "status": 1,
     *                                  "child": {
     *                                      {
     *                                          "id": 1283,
     *                                          "sort": 500,
     *                                          "parent_id": 1332,
     *                                          "created_at": "2022-07-07T07:37:54.000000Z",
     *                                          "updated_at": "2022-07-08T07:59:32.000000Z",
     *                                          "psic_code": "08528005002000000",
     *                                          "psic_text": "ТВ приставка ЭЛЕКТРИЧЕСКИЕ МАШИНЫ И ОБОРУДОВАНИЕ, ИХ ЧАСТИ\n Телевизоры и мониторы",
     *                                          "marketplace_id": 404,
     *                                          "status": 1,
     *                                          "child": {},
     *                                          "language": {
     *                                              "id": 2909,
     *                                              "language_code": "ru",
     *                                              "category_id": 1283,
     *                                              "title": "ТВ-приставки",
     *                                              "slug": "1283",
     *                                              "preview_text": "",
     *                                              "detail_text": "",
     *                                              "created_at": "2022-07-07T07:37:54.000000Z",
     *                                              "updated_at": "2022-07-07T07:37:54.000000Z"
     *                                          }
     *                                      },
     *                                      {
     *                                          "id": 1316,
     *                                          "sort": 500,
     *                                          "parent_id": 1332,
     *                                          "created_at": "2022-07-07T07:37:55.000000Z",
     *                                          "updated_at": "2022-07-08T07:59:32.000000Z",
     *                                          "psic_code": "08517007009000000",
     *                                          "psic_text": "Медиаплеер ЭЛЕКТРИЧЕСКИЕ МАШИНЫ И ОБОРУДОВАНИЕ, ИХ ЧАСТИ\n Телефоны",
     *                                          "marketplace_id": 405,
     *                                          "status": 1,
     *                                          "child": {},
     *                                          "language": {
     *                                              "id": 2975,
     *                                              "language_code": "ru",
     *                                              "category_id": 1316,
     *                                              "title": "Медиаплееры-ресиверы",
     *                                              "slug": "1316",
     *                                              "preview_text": "",
     *                                              "detail_text": "",
     *                                              "created_at": "2022-07-07T07:37:55.000000Z",
     *                                              "updated_at": "2022-07-07T07:37:55.000000Z"
     *                                          }
     *                                      }
     *                                  },
     *                                  "language": {
     *                                      "id": 3007,
     *                                      "language_code": "ru",
     *                                      "category_id": 1332,
     *                                      "title": "ТВ-приставки и медиаплееры",
     *                                      "slug": "1332",
     *                                      "preview_text": "",
     *                                      "detail_text": "",
     *                                      "created_at": "2022-07-07T07:37:55.000000Z",
     *                                      "updated_at": "2022-07-07T07:37:55.000000Z"
     *                                  }
     *                              },
     *                              {
     *                                  "id": 1341,
     *                                  "sort": 500,
     *                                  "parent_id": 1245,
     *                                  "created_at": "2022-07-07T07:37:55.000000Z",
     *                                  "updated_at": "2022-07-08T07:59:33.000000Z",
     *                                  "psic_code": "09403001009000000",
     *                                  "psic_text": "Подставка для телевизора МЕБЕЛЬ, КРОВАТИ И СВЕТИЛЬНИКИ Мебель прочая и ее части",
     *                                  "marketplace_id": 361,
     *                                  "status": 1,
     *                                  "child": {
     *                                      {
     *                                          "id": 1342,
     *                                          "sort": 500,
     *                                          "parent_id": 1341,
     *                                          "created_at": "2022-07-07T07:37:55.000000Z",
     *                                          "updated_at": "2022-07-08T07:59:33.000000Z",
     *                                          "psic_code": "09403001009000000",
     *                                          "psic_text": "Подставка для телевизора МЕБЕЛЬ, КРОВАТИ И СВЕТИЛЬНИКИ Мебель прочая и ее части",
     *                                          "marketplace_id": 362,
     *                                          "status": 1,
     *                                          "child": {},
     *                                          "language": {
     *                                              "id": 3027,
     *                                              "language_code": "ru",
     *                                              "category_id": 1342,
     *                                              "title": "Настенное крепление",
     *                                              "slug": "1342",
     *                                              "preview_text": "",
     *                                              "detail_text": "",
     *                                              "created_at": "2022-07-07T07:37:55.000000Z",
     *                                              "updated_at": "2022-07-07T07:37:55.000000Z"
     *                                          }
     *                                      },
     *                                      {
     *                                          "id": 1343,
     *                                          "sort": 500,
     *                                          "parent_id": 1341,
     *                                          "created_at": "2022-07-07T07:37:55.000000Z",
     *                                          "updated_at": "2022-07-08T07:59:33.000000Z",
     *                                          "psic_code": "09403001009000000",
     *                                          "psic_text": "Подставка для телевизора МЕБЕЛЬ, КРОВАТИ И СВЕТИЛЬНИКИ Мебель прочая и ее части",
     *                                          "marketplace_id": 363,
     *                                          "status": 1,
     *                                          "child": {},
     *                                          "language": {
     *                                              "id": 3029,
     *                                              "language_code": "ru",
     *                                              "category_id": 1343,
     *                                              "title": "Потолочное крепление",
     *                                              "slug": "1343",
     *                                              "preview_text": "",
     *                                              "detail_text": "",
     *                                              "created_at": "2022-07-07T07:37:55.000000Z",
     *                                              "updated_at": "2022-07-07T07:37:55.000000Z"
     *                                          }
     *                                      },
     *                                      {
     *                                          "id": 2010,
     *                                          "sort": 500,
     *                                          "parent_id": 1341,
     *                                          "created_at": "2022-07-07T07:38:18.000000Z",
     *                                          "updated_at": "2022-07-08T07:59:43.000000Z",
     *                                          "psic_code": "09403001009000000",
     *                                          "psic_text": "Подставка для телевизора МЕБЕЛЬ, КРОВАТИ И СВЕТИЛЬНИКИ Мебель прочая и ее части",
     *                                          "marketplace_id": 1127,
     *                                          "status": 1,
     *                                          "child": {},
     *                                          "language": {
     *                                              "id": 4363,
     *                                              "language_code": "ru",
     *                                              "category_id": 2010,
     *                                              "title": "Настольное крепление",
     *                                              "slug": "2010",
     *                                              "preview_text": "",
     *                                              "detail_text": "",
     *                                              "created_at": "2022-07-07T07:38:18.000000Z",
     *                                              "updated_at": "2022-07-07T07:38:18.000000Z"
     *                                          }
     *                                      }
     *                                  },
     *                                  "language": {
     *                                      "id": 3025,
     *                                      "language_code": "ru",
     *                                      "category_id": 1341,
     *                                      "title": "Кронштейны и подставки",
     *                                      "slug": "1341",
     *                                      "preview_text": "",
     *                                      "detail_text": "",
     *                                      "created_at": "2022-07-07T07:37:55.000000Z",
     *                                      "updated_at": "2022-07-07T07:37:55.000000Z"
     *                                  }
     *                              }
     *                          },
     *                          "language": {
     *                              "id": 2833,
     *                              "language_code": "ru",
     *                              "category_id": 1245,
     *                              "title": "Телевизоры и видеотехника",
     *                              "slug": "1245",
     *                              "preview_text": "",
     *                              "detail_text": "",
     *                              "created_at": "2022-07-07T07:37:53.000000Z",
     *                              "updated_at": "2022-07-07T07:37:53.000000Z"
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

    //buyer/pay-services/list
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

    //buyer/bonus-balance
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

    //buyer/bonus-to-card
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

    //buyer/bonus-to-card-confirm
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

    //buyer/pay-services/pay
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
}
