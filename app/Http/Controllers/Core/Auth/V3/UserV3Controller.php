<?php

namespace App\Http\Controllers\Core\Auth\V3;

use App\Http\Controllers\Controller;
use App\Http\Requests\V3\User\UpdateRequest;
use App\Http\Response\BaseResponse;
use App\Models\V3\UserV3;
use Illuminate\Http\JsonResponse;

class UserV3Controller extends Controller
{
    /**
     * @OA\Get (
     *     summary="Получение пользователей",
     *     path="/api/v3/user/list",
     *     description="Получение пользователей",
     *     tags={"users"},
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
     *                                     "current_page":1,
     *                                     "data":{
     *                                              "id" : 1,
     *                                              "email": null,
     *                                              "name": "Администратор",
     *                                              "surname": "Admin",
     *                                              "patronymic": "Admin",
     *                                              "gender": "1",
     *                                              "birth_date": null,
     *                                              "region": null,
     *                                              "local_region": null,
     *                                              "email_verified_at": null,
     *                                              "verify_message": null,
     *                                              "status": 1,
     *                                              "status_employee": 1,
     *                                              "company_id": null,
     *                                              "seller_company_id": null,
     *                                              "seller_company_id": "2020-04-17T04:26:35.000000Z",
     *                                              "created_by": null,
     *                                              "updated_at": "2020-04-17T04:26:35.000000Z",
     *                                              "verified_at": null,
     *                                              "verified_by": null,
     *                                              "ticketit_admin": 1,
     *                                              "ticketit_agent": 0,
     *                                              "kyc_status": 0,
     *                                              "kyc_id": null,
     *                                              "is_saller": 0,
     *                                              "device_os": null,
     *                                              "lang": null,
     *                                              "doc_path": 0,
     *                                              "black_list": 0,
     *                                              "vip": 0,
     *                                              "role_id": 12,
     *                                           },
     *                                       "first_page_url": "http://test-dev.loc/api/v3/user/list?page=1",
     *                                       "from": 1,
     *                                       "last_page": 78528,
     *                                       "last_page_url": "http://test-dev.loc/api/v3/user/list?page=78528",
     *                                       "next_page_url": "http://test-dev.loc/api/v3/user/list?page=2",
     *                                       "path": "http://test-dev.loc/api/v3/user/list",
     *                                       "per_page":2,
     *                                       "prev_page_url":null,
     *                                       "to":2,
     *                                       "total":157056,
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
        return BaseResponse::success(UserV3::paginate());
    }


    /**
     * @OA\Get (
     *     summary="Получение одного пользователя",
     *     path="/api/v3/user/{id}",
     *     description="Получение одного пользователя",
     *     tags={"users"},
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
     *                                     "email": null,
     *                                     "name": "Администратор",
     *                                     "surname": "Admin",
     *                                     "patronymic": "Admin",
     *                                     "gender": "1",
     *                                     "birth_date": null,
     *                                     "region": null,
     *                                     "local_region": null,
     *                                     "email_verified_at": null,
     *                                     "verify_message": null,
     *                                     "status": 1,
     *                                     "status_employee": 1,
     *                                     "company_id": null,
     *                                     "seller_company_id": null,
     *                                     "seller_company_id": "2020-04-17T04:26:35.000000Z",
     *                                     "created_by": null,
     *                                     "updated_at": "2020-04-17T04:26:35.000000Z",
     *                                     "verified_at": null,
     *                                     "verified_by": null,
     *                                     "ticketit_admin": 1,
     *                                     "ticketit_agent": 0,
     *                                     "kyc_status": 0,
     *                                     "kyc_id": null,
     *                                     "is_saller": 0,
     *                                     "device_os": null,
     *                                     "lang": null,
     *                                     "doc_path": 0,
     *                                     "black_list": 0,
     *                                     "vip": 0,
     *                                     "role_id": 12,
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
    public function get(UserV3 $user): JsonResponse
    {
        return BaseResponse::success($user);
    }


    /**
     * @OA\Put  (
     *     summary="Получение одного пользователя",
     *     path="/api/v3/user/{id}/update",
     *     description="Получение одного пользователя",
     *     tags={"users"},
     *     security={{"bearer_token":{}}},
     *     @OA\Parameter(
     *      in="path",
     *      name="id",
     *      @OA\Schema(type="integer"),
     *      required=true,
     *     ),
     *     @OA\Parameter(
     *      in="query",
     *      name="role_id",
     *      @OA\Schema(type="integer"),
     *      required=true,
     *      description="Id роли",
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
     *                                     "email": null,
     *                                     "name": "Администратор",
     *                                     "surname": "Admin",
     *                                     "patronymic": "Admin",
     *                                     "gender": "1",
     *                                     "birth_date": null,
     *                                     "region": null,
     *                                     "local_region": null,
     *                                     "email_verified_at": null,
     *                                     "verify_message": null,
     *                                     "status": 1,
     *                                     "status_employee": 1,
     *                                     "company_id": null,
     *                                     "seller_company_id": null,
     *                                     "seller_company_id": "2020-04-17T04:26:35.000000Z",
     *                                     "created_by": null,
     *                                     "updated_at": "2020-04-17T04:26:35.000000Z",
     *                                     "verified_at": null,
     *                                     "verified_by": null,
     *                                     "ticketit_admin": 1,
     *                                     "ticketit_agent": 0,
     *                                     "kyc_status": 0,
     *                                     "kyc_id": null,
     *                                     "is_saller": 0,
     *                                     "device_os": null,
     *                                     "lang": null,
     *                                     "doc_path": 0,
     *                                     "black_list": 0,
     *                                     "vip": 0,
     *                                     "role_id": 12,
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
    public function update(UpdateRequest $request, UserV3 $user): JsonResponse
    {
        $user->fill($request->all())->save();
        return BaseResponse::success($user);
    }

}
