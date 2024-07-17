<?php

namespace App\Http\Controllers\V3;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Core\CardController as CoreCardController;
use App\Http\Requests\CardAddRequest;
use App\Services\API\V3\CardService;
use App\Services\API\V3\PartnerService;
use App\Services\API\V3\SlidesService;
use Illuminate\Http\Request;

class SlidesController extends CoreController
{
    protected SlidesService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new SlidesService();
    }

    /**
     * @OA\Get(
     *      path="/slides/list",
     *      tags={"Slides"},
     *      security={{"api_token_security":{}}},
     *      summary="Список слайдов (slides list)",
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
     *                      "total": 3,
     *                      "data": {
     *                          {
     *                              "id": 4,
     *                              "language_code": null,
     *                              "title": "gtyu",
     *                              "text": "<p>fsdfdf</p>",
     *                              "sort": 1,
     *                              "slider_id": 1,
     *                              "link": "http://yandex.ru",
     *                              "label": "yandex",
     *                              "created_at": "2021-08-12T06:01:52.000000Z",
     *                              "updated_at": "2021-08-12T06:01:52.000000Z",
     *                              "image": {
     *                                  "id": 191027,
     *                                  "element_id": 4,
     *                                  "model": "slide",
     *                                  "type": "image",
     *                                  "name": "78acad67c47a23dda1988281a73b5996.jpg",
     *                                  "path": "slide/4/78acad67c47a23dda1988281a73b5996.jpg",
     *                                  "user_id": 1,
     *                                  "language_code": null,
     *                                  "updated_at": "2021-08-12T06:01:52.000000Z",
     *                                  "created_at": "2021-08-12T06:01:52.000000Z",
     *                                  "doc_path": 0,
     *                                  "preview": "http://localhost/storage/slide/4/78acad67c47a23dda1988281a73b5996.jpg"
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
     *      path="/slides/detail/{id}",
     *      tags={"Slides"},
     *      security={{"api_token_security":{}}},
     *      summary="Подробная информация о слайдах (slides detailed)",
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
     *                      "id": 4,
     *                      "language_code": null,
     *                      "title": "gtyu",
     *                      "text": "<p>fsdfdf</p>",
     *                      "sort": 1,
     *                      "slider_id": 1,
     *                      "link": "http://yandex.ru",
     *                      "label": "yandex",
     *                      "created_at": "2021-08-12T06:01:52.000000Z",
     *                      "updated_at": "2021-08-12T06:01:52.000000Z",
     *                      "image": {
     *                          "id": 191027,
     *                          "element_id": 4,
     *                          "model": "slide",
     *                          "type": "image",
     *                          "name": "78acad67c47a23dda1988281a73b5996.jpg",
     *                          "path": "slide/4/78acad67c47a23dda1988281a73b5996.jpg",
     *                          "user_id": 1,
     *                          "language_code": null,
     *                          "updated_at": "2021-08-12T06:01:52.000000Z",
     *                          "created_at": "2021-08-12T06:01:52.000000Z",
     *                          "doc_path": 0,
     *                          "preview": "http://localhost/storage/slide/4/78acad67c47a23dda1988281a73b5996.jpg"
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
}
