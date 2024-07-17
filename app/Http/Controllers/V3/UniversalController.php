<?php

namespace App\Http\Controllers\V3;

use App\Services\API\V3\UniversalService;
use Illuminate\Http\Request;

class UniversalController extends CoreController
{
    protected UniversalService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new UniversalService();
    }

    /**
     * @OA\Post(
     *      path="/buyer/send-sms-code-uz",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод отправка четырехзначного кода смс на номер телефона для добавление карты платежей (Send SMS code for verification)",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="card",
     *          description="Card number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="exp",
     *          description="Expiration date of card",
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
     *              @OA\Schema(example={"status": "success","error":{},"data":{"hashed":"$2y$10$A2Hp0gSEOJIWCOgdMDNPvu71WVuJg4cYeXU8aEzlogIfaTp4NGeNu"}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                   example={"status": "error","error":{{"type":"danger","text":"Ushbu karta tizimga qo'shilib bo'lingan!"}},"data":{}}
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
    public function sendSmsCodeUniversal(Request $request)
    {
        return $this->service->sendSmsCodeCodeWithoutScoring($request);
    }

    /**
     * @OA\Post(
     *      path="/buyer/check-sms-code-uz",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод подтверждение четырехзначного кода смс для добавление карты платежей (Send sms code for confirm verification)",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="card_number",
     *          description="Card number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="card_valid_date",
     *          description="Expiration date of card",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="code",
     *          description="SMS code",
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
     *              @OA\Schema(example={"status": "success","error":{},"data":{}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(example={"status": "error","error":{{"type":"danger","text":"Invalid code"}},"data":{}})
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
    public function checkSmsCodeUniversal(Request $request)
    {
        return $this->service->checkSmsCodeUniversal($request);
    }
}
