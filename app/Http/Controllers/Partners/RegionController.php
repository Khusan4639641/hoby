<?php

namespace App\Http\Controllers\Partners;

use App\Http\Controllers\V3\CoreController;
use App\Services\API\V3\Partners\RegionService;
use Illuminate\Http\Request;

class RegionController extends CoreController
{
    protected RegionService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new RegionService();
    }

    /**
     * @OA\Get(
     *      path="/regions/list",
     *      tags={"Region"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод возвращает лист регионов",
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
     *                          "regionid": 1703,
     *                          "regioncode": 1703,
     *                          "regioncode2": 17,
     *                          "nameRu": "Андижанская область",
     *                          "nameUz": "Andijon viloyati",
     *                          "codelat": "AN",
     *                          "codecyr": "АН",
     *                          "admincenterRu": "г. Андижан",
     *                          "admincenterUz": "Andijon sh.",
     *                          "kadastrno": null,
     *                          "startdate": null,
     *                          "finishdate": null,
     *                          "rem": null,
     *                          "isdeleted": 0
     *                      },
     *                      {
     *                          "regionid": 1706,
     *                          "regioncode": 1706,
     *                          "regioncode2": 20,
     *                          "nameRu": "Бухарская область",
     *                          "nameUz": "Buxoro viloyati",
     *                          "codelat": "BX",
     *                          "codecyr": "БХ",
     *                          "admincenterRu": "г. Бухара",
     *                          "admincenterUz": "Buxoro sh.",
     *                          "kadastrno": null,
     *                          "startdate": null,
     *                          "finishdate": null,
     *                          "rem": null,
     *                          "isdeleted": 0
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
    public function list(Request $request)
    {
        return $this->service->list($request);
    }
}
