<?php

namespace App\Http\Controllers\V3;

use App\Http\Requests\V3\CatalogCategory\SearchByPsicCodeRequest;
use App\Http\Requests\V3\CatalogCategoryController\GetCategoriesHierarchyRequest;
use App\Services\API\V3\CatalogCategoryService;
use Illuminate\Http\Request;
use App\Http\Requests\AddCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\CatalogCategory;

class CatalogCategoryController extends CoreController
{
  protected CatalogCategoryService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new CatalogCategoryService();
    }

    /**
     * @OA\Get(
     *      path="/categories/list",
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
    public function list(Request $request)
    {
        return $this->service->list($request->all());
    }

    /**
     * @OA\Get(
     *      path="/categories/detail/{id}",
     *      tags={"Categories"},
     *      security={{"api_token_security":{}}},
     *      summary="Показывает Item по выбранной категории",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="id",
     *          description="ID of specific item",
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
     *                      "status": "success",
     *                      "error": {},
     *                      "data": {
     *                          "id": 2,
     *                          "sort": 500,
     *                          "parent_id": 0,
     *                          "title": "Gadjet va aksessuarlar",
     *                          "slug": null
     *                      }
     *                  })
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
    public function detail(Request $request, $id)
    {
        if(!(int)$id){
            $this->service->handleError([__('api.bad_request')]);
        }
        $request->merge(['id' => $id]);
        return $this->service->list($request->all());
    }

    public function treeList(Request $request)
    {
        return $this->service::treeList($request->all());
    }

    public function panelList(Request $request)
    {
        return $this->service::panelList($request->all());
    }

    public function all(Request $request)
    {
        $result = $this->service::all($request->all());
        return $result;
    }

    public function get(Request $request, CatalogCategory $catalog_category)
    {
      return $this->service::get($catalog_category);
    }

    public function add(AddCategoryRequest $request)
    {
        $result = $this->service::add($request);
        return $result;
    }

    public function update(UpdateCategoryRequest $request, CatalogCategory $catalog_category)
    {
        $result = $this->service::update($request, $catalog_category);
        return $result;
    }

    public function delete(Request $request, CatalogCategory $catalog_category)
    {
        $result = $this->service::delete($catalog_category);
        return $result;
    }

    public function searchByPsicCode(SearchByPsicCodeRequest $request)
    {
        return $this->service::searchByPsicCode($request->psic_code);
    }
    public function getCategoriesHierarchy(GetCategoriesHierarchyRequest $request)
    {
        return $this->service->getCategoriesHierarchy($request->search_value, $request->limit, $request->offset);
    }
}
