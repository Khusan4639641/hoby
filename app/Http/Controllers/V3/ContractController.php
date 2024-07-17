<?php

namespace App\Http\Controllers\V3;

use App\Http\Requests\ContractCancelRequest;
use App\Models\Contract;
use App\Services\API\V3\ContractService;
use Illuminate\Http\Request;

class ContractController extends CoreController
{
    protected ContractService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new ContractService();
    }

    /**
     * @OA\Post(
     *      path="/contracts/sign",
     *      tags={"Contracts"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод для онлайн подписи (buyer’s online signature)",
     *      description="Return json",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\MediaType(
     *              mediaType="multipart/form-data",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      description="Sign file",
     *                      property="sign",
     *                      type="file",
     *                 ),
     *                  @OA\Property(
     *                      description="Contract id",
     *                      property="id",
     *                      type="itenger",
     *                 ),
     *                  required={"sign","id"}
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"status": "error","error": {{"type": "danger","text": "Поле id обязательно для заполнения."}},"data": {}}
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
    public function signContract(Request $request)
    {
        return $this->service::signContract($request);
    }

    public function cancel(ContractCancelRequest $request)
    {
        return $this->service::cancelContract($request);
    }
}
