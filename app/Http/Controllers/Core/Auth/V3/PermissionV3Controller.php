<?php

namespace App\Http\Controllers\Core\Auth\V3;

use App\Http\Requests\V3\Permission\CreateRequest;
use App\Http\Requests\V3\Permission\UpdateRequest;
use App\Http\Response\BaseResponse;
use App\Models\V3\PermissionV3;
use Illuminate\Http\JsonResponse;


class PermissionV3Controller extends BaseV3Controller
{
    /**
     * @OA\Get(
     *     summary="Получение всех доступов",
     *     path="/api/v3/permission/list",
     *     description="permission",
     *     tags={"permission"},
     *     security={{"bearer_token":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         {"status": "success",
     *                         "response": {
     *                                 "code": 200,
     *                                 "message": {},
     *                                 "error": {},
     *                          },
     *                          "data": {
     *                                    {
     *                                        "id" : 1,
     *                                        "name": "add-news",
     *                                        "display_name": "Создавать новости",
     *                                        "description": "Возможность просмотреть новости",
     *                                        "created_at": "2020-04-17T05:42:41.000000Z",
     *                                        "updated_at": "2020-04-17T05:42:41.000000Z",
     *                                        "route_name": null,
     *                                      },
     *                                    {
     *                                        "id" : 2,
     *                                        "name": "modify-news",
     *                                        "display_name": "Редактировать  новости",
     *                                        "description": "Возможность просмотреть новости",
     *                                        "created_at": "2020-04-17T05:42:41.000000Z",
     *                                        "updated_at": "2020-04-17T05:42:41.000000Z",
     *                                        "route_name": null,
     *                                      },
     *                                   },
     *                          },
     *                     }
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
     *                         {"status": "error",
     *                         "response": {
     *                                 "code": 401,
     *                                 "message": {},
     *                                 "error": {},
     *                          },
     *                          "data": "Unauthorized",
     *                          },
     *                     }
     *                 )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Unauthenticated",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         {"status": "error",
     *                         "response": {
     *                                 "code": 403,
     *                                 "message": {},
     *                                 "error": {},
     *                          },
     *                          "data": "Access is denied",
     *                          },
     *                     }
     *                 )
     *          ),
     *      ),
     *     )
     */
    public function list(): JsonResponse
    {
        return BaseResponse::success(PermissionV3::all());
    }


    /**
     * @OA\Get (
     *     summary="Получение одного доступа",
     *     path="/api/v3/permission/{id}",
     *     description="Получение одного доступа",
     *     tags={"permission"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *      in="path",
     *      name="id",
     *      @OA\Schema(type="integer"),
     *      required=true,
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         {"status": "success",
     *                         "response": {
     *                                 "code": 200,
     *                                 "message": {},
     *                                 "error": {},
     *                          },
     *                          "data": {
     *                                     "id" : 1,
     *                                     "name": "add-news",
     *                                     "display_name": "Создавать новости",
     *                                     "description": "Возможность просмотреть новости",
     *                                     "created_at": "2020-04-17T05:42:41.000000Z",
     *                                     "updated_at": "2020-04-17T05:42:41.000000Z",
     *                                     "route_name": null,
     *                                   },
     *                          },
     *                     }
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
     *                         {"status": "error",
     *                         "response": {
     *                                 "code": 401,
     *                                 "message": {},
     *                                 "error": {},
     *                          },
     *                          "data": "Unauthorized",
     *                          },
     *                     }
     *                 )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Unauthenticated",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         {"status": "error",
     *                         "response": {
     *                                 "code": 403,
     *                                 "message": {},
     *                                 "error": {},
     *                          },
     *                          "data": "Access is denied",
     *                          },
     *                     }
     *                 )
     *          ),
     *      ),
     * )
     *
     */
    public function get(PermissionV3 $permission): JsonResponse
    {
        return BaseResponse::success($permission);
    }


    /**
     * @OA\Put (
     *     summary="Обновление одного доступа",
     *     path="/api/v3/permission/{id}/update",
     *     description="Обновление одного доступа",
     *     tags={"permission"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *      in="path",
     *      name="id",
     *      @OA\Schema(type="integer"),
     *      required=true,
     *     ),
     *     @OA\Parameter(
     *      in="query",
     *      name="name",
     *      @OA\Schema(type="string"),
     *      required=true,
     *      description="Название",
     *     ),
     *     @OA\Parameter(
     *     name="description",
     *     in="query",
     *     @OA\Schema(type="string"),
     *     required=true,
     *     description="Описание",
     *     ),
     *     @OA\Parameter(
     *     name="route_name",
     *     in="query",
     *     @OA\Schema(type="string"),
     *     required=true,
     *     description="Название роута",
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         {"status": "success",
     *                         "response": {
     *                                 "code": 200,
     *                                 "message": {},
     *                                 "error": {},
     *                          },
     *                          "data": {
     *                                     "id" : 1,
     *                                     "name": "add-news",
     *                                     "display_name": "Создавать новости",
     *                                     "description": "Возможность просмотреть новости",
     *                                     "created_at": "2020-04-17T05:42:41.000000Z",
     *                                     "updated_at": "2020-04-17T05:42:41.000000Z",
     *                                     "route_name": null,
     *                                   },
     *                          },
     *                     }
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
     *                         {"status": "error",
     *                         "response": {
     *                                 "code": 401,
     *                                 "message": {},
     *                                 "error": {},
     *                          },
     *                          "data": "Unauthorized",
     *                          },
     *                     }
     *                 )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Unauthenticated",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         {"status": "error",
     *                         "response": {
     *                                 "code": 403,
     *                                 "message": {},
     *                                 "error": {},
     *                          },
     *                          "data": "Access is denied",
     *                          },
     *                     }
     *                 )
     *          ),
     *      ),
     * )
     *
     */
    public function update(UpdateRequest $request, PermissionV3 $permission): JsonResponse
    {
        $permission->fill($request->all())->save();
        return BaseResponse::success($permission);
    }


    /**
     * @OA\Post (
     *     summary="Создание одного доступа",
     *     path="/api/v3/permission/create",
     *     description="Создание одного доступа",
     *     tags={"permission"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *      in="query",
     *      name="name",
     *      @OA\Schema(type="string"),
     *      required=true,
     *      description="Название",
     *     ),
     *     @OA\Parameter(
     *     name="description",
     *     in="query",
     *     @OA\Schema(type="string"),
     *     required=true,
     *     description="Описание",
     *     ),
     *     @OA\Parameter(
     *     name="route_name",
     *     in="query",
     *     @OA\Schema(type="string"),
     *     required=true,
     *     description="Название роута",
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         {"status": "success",
     *                         "response": {
     *                                 "code": 200,
     *                                 "message": {},
     *                                 "error": {},
     *                          },
     *                          "data": {
     *                                     "id" : 1,
     *                                     "name": "add-news",
     *                                     "display_name": "Создавать новости",
     *                                     "description": "Возможность просмотреть новости",
     *                                     "created_at": "2020-04-17T05:42:41.000000Z",
     *                                     "updated_at": "2020-04-17T05:42:41.000000Z",
     *                                     "route_name": null,
     *                                   },
     *                          },
     *                     }
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
     *                         {"status": "error",
     *                         "response": {
     *                                 "code": 401,
     *                                 "message": {},
     *                                 "error": {},
     *                          },
     *                          "data": "Unauthorized",
     *                          },
     *                     }
     *                 )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Unauthenticated",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         {"status": "error",
     *                         "response": {
     *                                 "code": 403,
     *                                 "message": {},
     *                                 "error": {},
     *                          },
     *                          "data": "Access is denied",
     *                          },
     *                     }
     *                 )
     *          ),
     *      ),
     * )
     *
     */
    public function create(CreateRequest $request): JsonResponse
    {
        $permission = new PermissionV3($request->all());
        $permission->save();
        return BaseResponse::success($permission);
    }


    /**
     * @OA\Delete  (
     *     summary="Удаление одного доступа",
     *     path="/api/v3/permission/{id}",
     *     description="Удаление одного доступа",
     *     tags={"permission"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *      in="path",
     *      name="id",
     *      @OA\Schema(type="integer"),
     *      required=true,
     *     ),
     *      @OA\Response(
     *          response=200,
     *          description="success",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         {"status": "success",
     *                         "response": {
     *                                 "code": 200,
     *                                 "message": {},
     *                                 "error": {},
     *                          },
     *                          "data": "success",
     *                          },
     *                     }
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
     *                         {"status": "error",
     *                         "response": {
     *                                 "code": 401,
     *                                 "message": {},
     *                                 "error": {},
     *                          },
     *                          "data": "Unauthorized",
     *                          },
     *                     }
     *                 )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Unauthenticated",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         {"status": "error",
     *                         "response": {
     *                                 "code": 403,
     *                                 "message": {},
     *                                 "error": {},
     *                          },
     *                          "data": "Access is denied",
     *                          },
     *                     }
     *                 )
     *          ),
     *      ),
     * )
     *
     */
    public function delete(PermissionV3 $permission): JsonResponse
    {
        $permission->delete();
        return BaseResponse::success('success');
    }
}
