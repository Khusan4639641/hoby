<?php

namespace App\Http\Controllers\Core\Auth\V3;

use App\Http\Controllers\Controller;
use App\Http\Requests\V3\Role\CreateRequest;
use App\Http\Requests\V3\Role\UpdateRequest;
use App\Http\Response\BaseResponse;
use App\Models\V3\RoleV3;
use Illuminate\Http\JsonResponse;

class RoleV3Controller extends Controller
{

    /**
     * @OA\Get (
     *     summary="Получение всех ролей",
     *     path="/api/v3/role/list",
     *     description="role",
     *     tags={"role"},
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
     *                                     {
     *                                     "id" : 1,
     *                                     "name": "Admin",
     *                                     "display_name": "Администратор",
     *                                     "description": "Администратор системы",
     *                                     "created_at": "2020-04-17T04:26:35.000000Z",
     *                                     "updated_at": "2020-04-17T04:26:35.000000Z",
     *                                     },
     *                                    {
     *                                     "id" : 2,
     *                                     "name": "owner",
     *                                     "display_name": "Владелец",
     *                                     "description": "Владелец системы",
     *                                     "created_at": "2020-04-17T04:26:35.000000Z",
     *                                     "updated_at": "2020-04-17T04:26:35.000000Z",
     *                                     },
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
     *
     */
    public function list(): JsonResponse
    {
        return BaseResponse::success(RoleV3::all());
    }


    /**
     * @OA\Get (
     *     summary="Обновление одной роли",
     *     path="/api/v3/role/{id}",
     *     description="Обновление одной роли",
     *     tags={"role"},
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
     *                                     "name": "Admin",
     *                                     "display_name": "Администратор",
     *                                     "description": "Администратор системы",
     *                                     "created_at": "2020-04-17T04:26:35.000000Z",
     *                                     "updated_at": "2020-04-17T04:26:35.000000Z",
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
     *   )
     *
     */
    public function get(RoleV3 $role): JsonResponse
    {
        $role->permissions = $role->permissions()->get()->pluck('id');
        return BaseResponse::success($role);
    }

    /**
     * @OA\Put (
     *     summary="Обновление одной роли",
     *     path="/api/v3/role/{id}/update",
     *     description="Обновление одной роли",
     *     tags={"role"},
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
     *         name="permission_id[]",
     *         in="query",
     *         description="ID доступов",
     *         required=false,
     *         @OA\Schema(
     *             type="array",
     *             @OA\Items(
     *                type="integer",
     *                 @OA\Property(
     *                     property="permission_id[]",
     *                     type="integer"
     *                 ),
     *            ),
     *         ),
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
     *                                     "name": "Admin",
     *                                     "display_name": "Администратор",
     *                                     "description": "Администратор системы",
     *                                     "created_at": "2020-04-17T04:26:35.000000Z",
     *                                     "updated_at": "2020-04-17T04:26:35.000000Z",
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
     *   )
     *
     */
    public function update(UpdateRequest $request, RoleV3 $role): JsonResponse
    {
        $role->fill($request->all())->save();
        $role->permissions()->sync($request->get('permission_id'));
        $role->permissions = $role->permissions()->get()->pluck('id');
        return BaseResponse::success($role);
    }

    /**
     * @OA\Post (
     *     summary="Создание одной роли",
     *     path="/api/v3/role/create",
     *     description="Создание одной роли",
     *     tags={"role"},
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
     *         name="permission_id[]",
     *         in="query",
     *         description="ID доступов",
     *         required=false,
     *         @OA\Schema(
     *             type="array",
     *             @OA\Items(
     *                type="integer",
     *                 @OA\Property(
     *                     property="permission_id[]",
     *                     type="integer"
     *                 ),
     *            ),
     *         ),
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
     *                                     "name": "Admin",
     *                                     "display_name": "Администратор",
     *                                     "description": "Администратор системы",
     *                                     "created_at": "2020-04-17T04:26:35.000000Z",
     *                                     "updated_at": "2020-04-17T04:26:35.000000Z",
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
     *   )
     *
     */
    public function create(CreateRequest $request): JsonResponse
    {
        $role = new RoleV3($request->all());
        $role->save();
        $role->permissions()->sync($request->get('permission_id'));
        $role->permissions = $role->permissions()->get()->pluck('id');
        return BaseResponse::success($role);
    }

    /**
     * @OA\Delete  (
     *     summary="Удаление одного роли",
     *     path="/api/v3/role/{id}",
     *     description="Удаление одного роли",
     *     tags={"role"},
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
     *   )
     *
     */
    public function delete(RoleV3 $role): JsonResponse
    {
        $role->delete();
        return BaseResponse::success('success');
    }
}
