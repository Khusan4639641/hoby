<?php

namespace App\Http\Controllers\V3;

use App\Http\Requests\V3\Card\TestCardAddRequest;
use App\Http\Requests\V3\Card\TestCardConfirmRequest;
use App\Services\API\V3\CardService;
use App\Services\API\V3\CardServiceV2;
use Illuminate\Http\Request;

class CardController extends CoreController
{
    protected CardService $service;
    protected CardServiceV2 $serviceV2;

    public function __construct()
    {
        parent::__construct();
        $this->service = new CardService();
        $this->serviceV2 = new CardServiceV2();
    }

    /**
     * @OA\Post(
     *      path="/cards/add",
     *      operationId="cards-add",
     *      tags={"Cards"},
     *      summary="Добавление основной карты",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *      @OA\Parameter(
     *          name="pan",
     *          description="Card number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="expiry",
     *          description="Expire date",
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
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"status": "error","error":{{"type":"danger","text":"app.card_not_found"}},"data":{}}
     *                 )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    public function add(TestCardAddRequest $request)
    {
        return $this->serviceV2->add($request);
    }

    public function checkCardMonthlyReceipts()
    {
        /* Success cases */

        $cardScoringData = [
            "jsonrpc" => "2.0",
            "id" => "123",
            "result" => [
                [
                    "date" => "Mar-2023",
                    "payments" => ["count" => 3, "amount" => 0],
                    "salaries" => ["count" => 0, "amount" => 35000000],
                    "p2pCredit" => ["count" => 2, "amount" => 0],
                    "month" => "Oct-2022",
                ],
                [
                    "date" => "Feb-2023",
                    "payments" => ["count" => 3, "amount" => 0],
                    "salaries" => ["count" => 0, "amount" => 35000000],
                    "p2pCredit" => ["count" => 2, "amount" => 0],
                    "month" => "Oct-2022",
                ],
                [
                    "date" => "Jan-2023",
                    "payments" => ["count" => 3, "amount" => 0],
                    "salaries" => ["count" => 0, "amount" => 35000000],
                    "p2pCredit" => ["count" => 2, "amount" => 0],
                    "month" => "Oct-2022",
                ],
                [
                    "date" => "Dec-2022",
                    "payments" => ["count" => 3, "amount" => 0],
                    "salaries" => ["count" => 0, "amount" => 0],
                    "p2pCredit" => ["count" => 2, "amount" => 0],
                    "month" => "Oct-2022",
                ],
            ],
            "error" => null,
        ];

        $result = $this->serviceV2->checkLastMonthsIncome($cardScoringData, 26);

        dd($result);
    }

    /**
     * @OA\Post(
     *      path="/cards/confirm",
     *      operationId="card-confirm",
     *      tags={"Cards"},
     *      summary="Подтверждение основной карты",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *      @OA\Parameter(
     *          name="pan",
     *          description="Card number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="code",
     *          description="OTP",
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
     *              @OA\Schema(example={"status": "success","error":{},"data":{"Karta muvaffaqiyatli qo`shildi!"}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"status": "error","error":{{"type":"danger","text":"sms code not equal"}},"data":{}}
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
    public function confirm(TestCardConfirmRequest $request)
    {
        return $this->serviceV2->confirm($request);
    }

    /**
     * @OA\Post(
     *      path="/cards/add-secondary",
     *      operationId="cards-add-secondary",
     *      tags={"Cards"},
     *      summary="Добавление дополнительной карты",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *      @OA\Parameter(
     *          name="pan",
     *          description="Card number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="expiry",
     *          description="Expire date",
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
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"status": "error","error":{{"type":"danger","text":"app.card_not_found"}},"data":{}}
     *                 )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    public function addSecondary(TestCardAddRequest $request)
    {
        return $this->serviceV2->addSecondary($request);
    }

    /**
     * @OA\Post(
     *      path="/cards/confirm-secondary",
     *      operationId="card-confirm-secondary",
     *      tags={"Cards"},
     *      summary="Подтверждение дополнительной карты",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
     *      @OA\Parameter(
     *          name="pan",
     *          description="Card number",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="code",
     *          description="OTP",
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
     *              @OA\Schema(example={"status": "success","error":{},"data":{"Karta muvaffaqiyatli qo`shildi!"}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"status": "error","error":{{"type":"danger","text":"sms code not equal"}},"data":{}}
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
    public function confirmSecondary(TestCardConfirmRequest $request)
    {
        return $this->serviceV2->confirmSecondary($request);
    }

    /* TODO: Remove after Test test */
    /**
     * @OA\Post(
     *      path="/card/add",
     *      operationId="card-add",
     *      tags={"Cards"},
     *      summary="Добавление дополнительной карты",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
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
     *          description="Expire date",
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
     *              @OA\Schema(example={"status": "success","error":{},"data":{"card_token":"b21d54d9b9dd13cceee6fb433d428f2d"}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"status": "error","error":{{"type":"danger","text":"app.card_not_found"}},"data":{}}
     *                 )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated",
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Forbidden"
     *      )
     *     )
     */
    public function cardAdd(Request $request)
    {
        return $this->service::cardAddV2($request);
    }

    /* TODO: Remove after Test test */
    /**
     * @OA\Post(
     *      path="/card/confirm",
     *      operationId="card-confirm",
     *      tags={"Cards"},
     *      summary="Подтверждение дополнительной карты (Card confirm)",
     *      description="Return json",
     *      security={{"api_token_security":{}}},
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
     *          name="card_token",
     *          description="Card token",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="code",
     *          description="OTP",
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
     *              @OA\Schema(example={"status": "success","error":{},"data":{"Karta muvaffaqiyatli qo`shildi!"}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"status": "error","error":{{"type":"danger","text":"sms code not equal"}},"data":{}}
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
    public function cardConfirm(Request $request)
    {
        return $this->service::cardConfirmV2($request);
    }
}
