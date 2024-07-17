<?php

namespace App\Http\Controllers\Partners;

use App\Aggregators\DebtCollectActionsAggregators\ActionsAggregator;
use App\Http\Controllers\V3\CoreController;
use App\Http\Requests\V3\PartnerActionStoreRequest;
use App\Models\Buyer;
use App\Models\Company;
use App\Models\Contract;
use App\Models\PartnerContractAction;
use App\Models\User;
use App\Services\API\V3\BaseService;
use App\Services\API\V3\Partners\ContractService;
use App\Services\MFO\MFOPaymentService;
use Illuminate\Http\Request;

class ContractController extends CoreController
{
    protected ContractService $service;

    public function __construct()
    {
        $this->service = new ContractService();
    }

    /**
     * @OA\Post(
     *      path="/contracts/send-cancel-sms",
     *      tags={"Contracts"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод отправляет otp код для отмены договора",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="contract_id",
     *          description="Contract ID",
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
     *              @OA\Schema(example={"status":"success","error":{},"data":{}})
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
     *                      "error": {
     *                          {
     *                              "type": "danger",
     *                              "text": "Договор не найден!"
     *                          }
     *                      },
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
    public function cancelContract(Request $request)
    {
        $this->service->validateCancelContract($request);
        return $this->service->cancelContract($request);
    }

    /**
     * @OA\Post(
     *      path="/contracts/check-cancel-sms",
     *      tags={"Contracts"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод подтверждение otp кода для отмены договора",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="contract_id",
     *          description="Contract ID",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="code",
     *          description="Confirmation code",
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
     *              @OA\Schema(example={"status":"success","error":{},"data":{}})
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
     *                      "error": {
     *                          {
     *                              "type": "danger",
     *                              "text": "СМС код неверный"
     *                          }
     *                      },
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
    public function checkCancelSms(Request $request)
    {
        $this->service->validateCheckCancelSms($request);
        return $this->service->checkCancelSms($request);
    }

    /**
     * @OA\Post(
     *      path="/contracts/upload-act",
     *      tags={"Contracts"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод для загрузки акта ",
     *      description="Return json",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      description="Act file",
     *                      property="act",
     *                      type="file",
     *                 ),
     *                  @OA\Property(
     *                      description="Contract ID",
     *                      property="id",
     *                      type="itenger",
     *                 ),
     *                  required={"act","id"}
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
     *                  "data": {
     *                      "path": "contract/8966/caa0a7f75370ebae5c44f1bd8ab66dfc.png",
     *                      "message": "Akt yuklandi va moderatsiyaga yuborildi!"
     *                  }
     *              })
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"status": "error","error": {{"type": "danger","text": "Element topilmadi yoki o`chirildi!"}},"data": {}}
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
    public function uploadAct(Request $request)
    {
        $this->service->validateUploadAct($request);
        return $this->service->uploadAct($request->all());
    }

    /**
     * @OA\Post(
     *      path="/contracts/upload-imei",
     *      tags={"Contracts"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод для загрузки фото IMEI",
     *      description="Return json",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      description="Imei file",
     *                      property="imei",
     *                      type="file",
     *                 ),
     *                  @OA\Property(
     *                      description="Contract ID",
     *                      property="id",
     *                      type="itenger",
     *                 ),
     *                  required={"imei","id"}
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
     *                  "data": {
     *                      "path": "contract/8966/caa0a7f75370ebae5c44f1bd8ab66dfc.png",
     *                      "message": "IMEI yuklandi va moderatsiya ostida"
     *                  }
     *              })
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"status": "error","error": {{"type": "danger","text": "Element topilmadi yoki o`chirildi!"}},"data": {}}
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
    public function uploadImei(Request $request)
    {
        $this->service->validateUploadImei($request);
        return $this->service->uploadImei($request->all());
    }

    /**
     * @OA\Post(
     *      path="/contracts/upload-client-photo",
     *      tags={"Contracts"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод для загрузки фото клиента с товаром",
     *      description="Return json",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      description="Client photo",
     *                      property="client_photo",
     *                      type="file",
     *                 ),
     *                  @OA\Property(
     *                      description="Contract ID",
     *                      property="id",
     *                      type="itenger",
     *                 ),
     *                  required={"client_photo","id"}
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
     *                  "data": {
     *                      "path": "contract/8966/caa0a7f75370ebae5c44f1bd8ab66dfc.png",
     *                      "message": "Фото с клиентом загружено и находится на модерации"
     *                  }
     *              })
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"status": "error","error": {{"type": "danger","text": "Element topilmadi yoki o`chirildi!"}},"data": {}}
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
    public function uploadClientPhoto(Request $request)
    {
        $this->service->validateUploadClientPhoto($request);
        return $this->service->uploadClientPhoto($request->all());
    }
    /**
     * @OA\Post(
     *      path="/contracts/cancel-request",
     *      tags={"Contracts"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод отправляет запрос на отмену договора со стороны филиалла для головного",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="contract_id",
     *          description="Contract ID",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="reason",
     *          description="Cancellation reason",
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
     *              @OA\Schema(example={"status":"success","error":{},"data":{}})
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
     *                      "error": {
     *                          {
     *                              "type": "danger",
     *                              "text": "Договор не найден!"
     *                          }
     *                      },
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
    public function cancellationContractRequest(Request $request) {
      $this->service->cancellationContractRequest($request);
      return $this->service->handleResponse();
    }

    /**
     * @OA\Post(
     *      path="/contracts/reject-cancel-request",
     *      tags={"Contracts"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод отправляет запрос на отмену полученного заявки на отмену договора со стороны филлиала у головнога",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="contract_id",
     *          description="Contract ID",
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
     *              @OA\Schema(example={"status":"success","error":{},"data":{}})
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
     *                      "error": {
     *                          {
     *                              "type": "danger",
     *                              "text": "Договор не найден!"
     *                          }
     *                      },
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
    public function rejectCancellationContractRequest(Request $request) {
      $this->service->rejectCancellationContractRequest($request);
      return $this->service->handleResponse();
    }

    public function contractDetail(Request $request) {
        $response = $this->service->getContractDetail($request);
        return $this->service->handleResponse($response);
    }

    public function storeAction(PartnerActionStoreRequest $request, Contract $contract)
    {
        if ($contract->partner_id !== \Auth::id()){
            return BaseService::errorJson([__('app.err_not_found')], 'error', 404);
        }

        return $this->service->storeAction($request, $contract);
    }
}
