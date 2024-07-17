<?php

namespace App\Http\Controllers\V3\KYC;

use App\Http\Controllers\Controller;
use App\Http\Requests\V3\Buyer\UploadPassportAndIDRequest;
use App\Services\API\V3\BaseService;
use App\Services\API\V3\KYC\KYCMyidService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class KYCOrderController extends Controller
{

    public KYCMyidService $KYCMyidService;

    public function __construct()
    {
        $this->KYCMyidService = new KYCMyidService;
    }

    public function validateUploadDocuments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id',
        ]);
        if ($validator->fails()) {
            BaseService::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public function validateGetById(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:kyc_myid_verifications,id',
        ]);
        if ($validator->fails()) {
            BaseService::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public function validateApproveMyidRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:kyc_myid_verifications,id',
        ]);
        if ($validator->fails()) {
            BaseService::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public function validateRejectMyidRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:kyc_myid_verifications,id',
            'status' => 'nullable|integer'
        ]);
        if ($validator->fails()) {
            BaseService::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    /**
     * @OA\Post(
     *      path="/kyc/docs",
     *      tags={"KCY-MFO"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод загружает данные пользователя для ручной идентификации для KYC отдела",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="contract_id",
     *          description="Id Контракта",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="passport_type",
     *          description="Тип паспорта 6 или 0",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="passport_selfie",
     *          description="Селфи с пасспортом (обязателен в случаи выбора паспорта как документа)",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="file"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="id_selfie",
     *          description="Селфи с id картой (обязателен в случаи выбора id карты как документа)",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="file"
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
     *          response=400,
     *          description="Bad Request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                       "status": "error",
     *                       "error": {
     *                           {
     *                               "type": "danger",
     *                               "text": "Поле step обязательно для заполнения."
     *                           }
     *                       },
     *                       "data": {}
     *                   }
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
    public function uploadDocuments(UploadPassportAndIDRequest $request)
    {
        $this->validateUploadDocuments($request);
        $this->KYCMyidService->uploadDocuments($request);
        return BaseService::handleResponse();

    }

    /**
     * @OA\Get (
     *     path="/kyc/docs",
     *     tags={"KCY-MFO"},
     *     summary="Метод возвращает список заявок по ручной идентификации",
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
    public function getList()
    {
        $records = $this->KYCMyidService->getList();
        BaseService::handleResponse($records);
    }

    /**
     * @OA\Get (
     *     path="/kyc/docs/{id}",
     *     tags={"KCY-MFO"},
     *     summary="Метод возвращает детальную информацию заявки по ручной идентификации",
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
    public function getById($id)
    {
        $record = $this->KYCMyidService->index($id);
        BaseService::handleResponse($record);
    }

    /**
     * @OA\Post(
     *      path="/kyc/docs/approve",
     *      tags={"KCY-MFO"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод одобряет заявку пользователя при ручной идентификации и оповещает пользователя с помощью смс",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="id",
     *          description="ID of kyc_myid_verifications",
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
     *              @OA\Schema(example={
     *                  "status": "success",
     *                  "error": {},
     *                  "data": {}
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
     *          response=400,
     *          description="Bad Request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                       "status": "error",
     *                       "error": {
     *                           {
     *                               "type": "danger",
     *                               "text": "Поле step обязательно для заполнения."
     *                           }
     *                       },
     *                       "data": {}
     *                   }
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
    public function approveKYCMyidRequest(Request $request)
    {
        $this->validateApproveMyidRequest($request);
        $this->KYCMyidService->approveRequest($request['id']);
        BaseService::handleResponse();
    }

    /**
     * @OA\Post(
     *      path="/kyc/docs/reject",
     *      tags={"KCY-MFO"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод отклоняет заявку пользователя при ручной идентификации и оповещает пользователя с помощью смс",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="id",
     *          description="ID of kyc_myid_verifications",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="status",
     *          description="0 и 1 для того или иного пользователя в зависимости от параметра улетит та или иная смс пользователя",
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
     *              @OA\Schema(example={
     *                  "status": "success",
     *                  "error": {},
     *                  "data": {}
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
     *          response=400,
     *          description="Bad Request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                       "status": "error",
     *                       "error": {
     *                           {
     *                               "type": "danger",
     *                               "text": "Поле step обязательно для заполнения."
     *                           }
     *                       },
     *                       "data": {}
     *                   }
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
    public function rejectKYCMyidRequest(Request $request)
    {
        $this->validateRejectMyidRequest($request);
        $this->KYCMyidService->rejectRequest($request['id'], $request['status'] ?? 0);
        BaseService::handleResponse();
    }
}
