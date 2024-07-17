<?php

namespace App\Http\Controllers\V3;


use App\Services\API\V3\PartnerService;
use Illuminate\Http\Request;

class PartnerController extends CoreController
{
    protected PartnerService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new PartnerService();
    }

    /**
     * @OA\Get(
     *      path="/partners/list",
     *      tags={"Partners"},
     *      security={{"api_token_security":{}}},
     *      summary="Список партнеров по категориям (list partners of categories)",
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
     *                          "sort": 500,
     *                          "parent_id": 0,
     *                          "title": "Telefon va smartfonlar",
     *                          "slug": "11",
     *                          "items": {
     *                              {
     *                                  "catalog_id": 1,
     *                                  "partner_id": 215035,
     *                                  "id": 215035,
     *                                  "name": "ООО MAC BRO",
     *                                  "description": "MacBro – сеть магазинов и официальный сервис центр специализирующийся на технике APPLE и других всемирно известных брэндов. У нас вы можете найти всю продукцию Apple. Уже более 12 лет мы консультируем и подбираем нужный гаджет для наших покупателей.",
     *                                  "brand": "MacBro",
     *                                  "region_id": 1726,
     *                                  "address": "г. Ташкент, Шайхонтохурский р-н, ул. А. Навоий, дом 27",
     *                                  "legal_address": "г. Ташкент, Шайхонтохурский р-н, ул. А. Навоий, дом 27",
     *                                  "status": 0,
     *                                  "created_at": "2021-06-14 09:13:10",
     *                                  "updated_at": "2022-01-05 09:41:29",
     *                                  "website": "macbro.uz",
     *                                  "phone": "998787772020",
     *                                  "lat": "41.321356",
     *                                  "lon": "69.253175",
     *                                  "nameRu": "г. Ташкент",
     *                                  "nameUz": "Toshkent shahri",
     *                                  "codelat": "TN",
     *                                  "codecyr": "ТН",
     *                              }
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
    public function list(Request $request)
    {
        return $this->service->list($request);
    }

    /**
     * @OA\Get(
     *      path="/partners/detail/{id}",
     *      tags={"Partners"},
     *      security={{"api_token_security":{}}},
     *      summary="Get specific partner details by ID",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="id",
     *          description="ID",
     *          required=true,
     *          in="path",
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
     *                      "id": 215043,
     *                      "name": "ООО «INFINITE LIFE»",
     *                      "logo": "company/215043/7ff4f1d1c3781f373de2cef89d4ce796.png",
     *                      "description": null,
     *                      "brand": "INFINITE LIFE",
     *                      "region_id": 1726,
     *                      "address": "Ферганская обл., г.Бешарык Бешарыкский р-н, ул. Олтин Водий",
     *                      "legal_address": "Ферганская обл., г.Бешарык Бешарыкский р-н, ул. Олтин Водий",
     *                      "status": 0,
     *                      "created_at": "24.06.2021",
     *                      "updated_at": "2022-01-02T07:14:43.000000Z",
     *                      "website": null,
     *                      "phone": "998889565565",
     *                      "lat": null,
     *                      "lon": null,
     *                      "nameRu": "г. Ташкент",
     *                      "nameUz": "Toshkent shahri",
     *                      "codelat": "TN",
     *                      "codecyr": "ТН",
     *                      "categories": {
     *                          {
     *                              "catalog_id": 1,
     *                              "partner_id": 215043
     *                          },
     *                          {
     *                              "catalog_id": 2,
     *                              "partner_id": 215043
     *                          },
     *                          {
     *                              "catalog_id": 3,
     *                              "partner_id": 215043
     *                          },
     *                          {
     *                              "catalog_id": 4,
     *                              "partner_id": 215043
     *                          },
     *                          {
     *                              "catalog_id": 5,
     *                              "partner_id": 215043
     *                          },
     *                          {
     *                              "catalog_id": 6,
     *                              "partner_id": 215043
     *                          },
     *                          {
     *                              "catalog_id": 7,
     *                              "partner_id": 215043
     *                          },
     *                          {
     *                              "catalog_id": 8,
     *                              "partner_id": 215043
     *                          },
     *                          {
     *                              "catalog_id": 9,
     *                              "partner_id": 215043
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
    public function detail($id)
    {
        return $this->service->detail($id);
    }

    /**
     * @OA\Get(
     *      path="/partners/{id}/settings",
     *      tags={"Partners"},
     *      security={{"api_token_security":{}}},
     *      summary="Get specific partner settings by ID",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="id",
     *          description="ID",
     *          required=true,
     *          in="path",
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
     *                      "id": 215043,
     *                      "name": "ООО «INFINITE LIFE»",
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
    public function settings($id)
    {
        return $this->service->settings($id);
    }
}
