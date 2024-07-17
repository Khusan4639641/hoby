<?php

namespace App\Http\Controllers\V3;

use App\Services\API\V3\NewsService;
use Illuminate\Http\Request;

class NewsController extends CoreController
{
    protected NewsService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new NewsService();
    }
    /**
     * @OA\Get(
     *      path="/news/list",
     *      tags={"News"},
     *      security={{"api_token_security":{}}},
     *      summary="Лист новостей (News list)",
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
     *                      {
     *                       "id": 37,
     *                       "title": "Добро пожаловать, MEDIAPARK!",
     *                       "slug": "dobro-pojalovat--mediapark",
     *                       "created_at": "2021-11-02T06:45:52.000000Z",
     *                       "updated_at": "2021-11-02T06:45:52.000000Z",
     *                       "date": "02.11.2021",
     *                       "preview_text": "<p>Встречайте нашего нового партнера &ndash; сеть магазинов бытовой техники и электроники MEDIAPARK.</p>",
     *                       "detail_text": "<p class='MsoNormal'>Встречайте нашего нового партнера &ndash; сеть магазинов бытовой техники и электроники </span>MEDIAPARK<span>. Отныне для оформления рассрочки в данной сети достаточно быть пользователем платформы </span>Test<span>.</span></p>"
     *                   }
     *                 }
     *               })
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
        return $this->service->list($request->all());
    }

    /**
     * @OA\Get(
     *      path="/news/detail/{id}",
     *      tags={"News"},
     *      security={{"api_token_security":{}}},
     *      summary="Single news",
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
     *                      "id": 37,
     *                      "title": "Добро пожаловать, MEDIAPARK!",
     *                      "slug": "dobro-pojalovat--mediapark",
     *                      "created_at": "2021-11-02T06:45:52.000000Z",
     *                      "updated_at": "2021-11-02T06:45:52.000000Z",
     *                      "date": "02.11.2021",
     *                      "preview_text": "<p>Встречайте нашего нового партнера &ndash; сеть магазинов бытовой техники и электроники MEDIAPARK.</p>",
     *                      "detail_text": "<p class='MsoNormal'>Встречайте нашего нового партнера &ndash; сеть магазинов бытовой техники и электроники </span>MEDIAPARK<span>. Отныне для оформления рассрочки в данной сети достаточно быть пользователем платформы </span>Test<span>.</span></p>"
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
    public function detail(Request $request, $id)
    {
        if(!(int)$id){
            $this->service::handleError([__('api.bad_request')]);
        }
        $request->merge(['id' => $id]);
        return $this->service->list($request->all());
    }
}
