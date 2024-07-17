<?php

namespace App\Http\Controllers\V3;

use App\Services\API\V3\CompatibleApiService;
use Illuminate\Http\Request;

class CompatibleApiController extends CoreController
{
    protected CompatibleApiService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new CompatibleApiService();
    }

    /**
     * @OA\Post(
     *      path="/buyers/send-code-sms",
     *      tags={"Buyers"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод отправка четырехзначного кода смс на номер телефона для активации контракта (SMS code contract for buyer)",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="contract_id",
     *          description="ID of contract",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="phone",
     *          description="Buyer phone",
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
     *              @OA\Schema(example={"status": "success","error":{},"data":{"hashed":"$2y$10$DKQl6vG4bwU0kw1z7OEDceOPuvhdUlMdYUeKhBODBLwAIk\/2uoE06"}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(example={"status": "error","error":{{"type":"danger","text":"contract not found"}},"data":{}})
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
    public function SendContractSmsCode(Request $request)
    {
        return $this->service::SendContractSmsCode($request);
    }

    /**
     * @OA\Post(
     *      path="/buyers/check-code-sms",
     *      tags={"Buyers"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод подтверждение четырехзначного кода смс для активации контракта (Contract confirm by buyer)",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="contract_id",
     *          description="ID of contract",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="phone",
     *          description="Buyer phone",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="code",
     *          description="OTP Code",
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
     *              @OA\Schema(example={"status": "success","error":{},"data":{"contract_id":64634,"message":"Shartnoma muvaffaqiyatli tasdiqlandi! "}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(example={"status": "error","error":{{"type":"danger","text":"wrong sms code"}},"data":{}})
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
    public function CheckContractSmsCode(Request $request)
    {
        return $this->service::CheckContractSmsCode($request);
    }
}
