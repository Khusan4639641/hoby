<?php

namespace App\Http\Controllers\V3;

use App\Http\Requests\V3\Prepay\PrepayFreePayRequest;
use App\Http\Requests\V3\Prepay\PrepayMonthRequest;
use App\Http\Requests\V3\Prepay\PrepaySeveralMonthsRequest;

use App\Models\Payment;
use App\Services\API\V3\BaseService;
use App\Services\API\V3\FcmService;
use App\Services\API\V3\LoginService;

use App\Models\Buyer;

use App\Helpers\EncryptHelper;

use App\Http\Controllers\Controller;

use OpenApi\Annotations as OA;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;

use App\Traits\SmsTrait;


// dev_nurlan 02.06.2022
class PrepayController extends Controller
{
    use SmsTrait;

    public function makeRequest($url, $data): array
    {
        Log::channel('payment')->info($data);
        $response = Http::withHeaders([ 'Accept' => 'application/json' ])->post($url, $data);
        Log::channel('payment')->info("PrepayController. Code: {$response->status()}, Response:");
        Log::channel('payment')->info($response->json());
        return [$response->json(), $response->status()];
    }

    public function prepareResponse($response, $http_code, $type, $otp = null): void {

        if ( !isset($response["code"]) ) {
            BaseService::handleError([__('Test_card_service/code.default')]);
        }

        if ( ( $http_code !== 200 ) && ( $response["code"] !== 0 ) ) {
            $error_code = (string) $response["code"];

            if (Lang::get('test_card_service/code'.$error_code)) {
                BaseService::handleError( [ __('test_card_service/code')[$error_code] ] );
            }

            BaseService::handleError( [ __('test.internal_server_error') ] );
        }

        if (!is_null($otp) || $type === Payment::PAYMENT_SYSTEM_ACCOUNT) {
            BaseService::handleResponse([__('test.successfully_paid')]);
        }

        BaseService::handleResponse([__('test.otp_successfully_sent')]);
    }

    /**
     * @OA\Post(
     *      path="/contract/prepay/month",
     *      tags={"Contract"},
     *      security={{"api_token_security":{}}},
     *      summary="Payment",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="type",
     *          description="Type",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="card_id",
     *          description="Card id",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
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
     *          name="user_id",
     *          description="User ID",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="otp",
     *          description="OTP",
     *          required=false,
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
     *              @OA\Schema(example={"status": "success","error":{},"data":{"OK"}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"status": "error","error":{{"type":"danger","text":"Недостаточно денег!"}},"data":{}}
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
    protected function prePayMonth(PrepayMonthRequest $request): void {
        $type             = $request->validated()["type"];   // "CARD" или "ACCOUNT"
        $otp              = $request->validated()["otp"] ?? null;

        // Для Java Prepay:
        $user_id          = $request->validated()["user_id"] ?? Auth::user()->id;
        $contract_id      = $request->validated()["contract_id"] ?? null;
        $card_id          = $request->validated()["card_id"] ?? null;

        $url_month = Config::get('test.test');

        $data = [
            "type" => $type,  // "CARD" или "ACCOUNT"
            "user_id" => $user_id,
            "contract_id" => $contract_id,
        ];

        if ( $type === "CARD" ) {
            $url_month = Config::get('test.test');
            $data["card_id"] = $card_id;

            $buyer = Buyer::with("card")->find($user_id);
            $requestPhone = new Request();
            $requestPhone->merge(['phone' => correct_phone($buyer->phone)]);

            if ($request->has('otp')) {
//                $data["otp"] = $otp; // 24.03.2023 Jav'исты сказали что otp не используется и просто легас, можно отправлять без него

                $requestPhone->merge(['code' => $otp]);
                $sms_response = LoginService::checkSmsCode($requestPhone);
                if ($sms_response['code'] === 0) {
                    BaseService::handleError([ __('auth.error_code_wrong') ]);
                }
            } else {
                $str = EncryptHelper::decryptData($buyer->card->card_number);
                $card_number = '****' . substr($str, -4);
                $msg = "Hurmatli mijoz, sizning " . $card_number . " kartangiz tomonidan muddatidan oldin to'lov qilish uchun tasdiqlash kodi: :code Tel: " . callCenterNumber(2);
                $returned = $this->sendSmsCode($requestPhone, true, $msg);
                if ( $returned["status"] === "success" ) {
                    BaseService::handleResponse($returned);
                }
                BaseService::handleError($returned);
            }
        }

        [$response, $http_code] = $this->makeRequest($url_month, $data);
        FcmService::notifyIfContractComplete($contract_id);
        $this->prepareResponse($response, $http_code, $type, $otp); // END
    }

    /**
     * @OA\Post(
     *      path="/contract/prepay/month/confirm",
     *      tags={"Contract"},
     *      security={{"api_token_security":{}}},
     *      summary="Payment",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="type",
     *          description="Type",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="card_id",
     *          description="Card id",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
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
     *          name="user_id",
     *          description="User ID",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="otp",
     *          description="OTP",
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
     *              @OA\Schema(example={"status": "success","error":{},"data":{"OK"}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"status": "error","error":{{"type":"danger","text":"Недостаточно денег!"}},"data":{}}
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
    /*********************************** */

    /**
     * @OA\Post(
     *      path="/contract/prepay/several-month",
     *      tags={"Contract"},
     *      security={{"api_token_security":{}}},
     *      summary="Payment",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="type",
     *          description="Type",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="card_id",
     *          description="Card id",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
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
     *          name="user_id",
     *          description="User ID",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="schedule_ids",
     *          description="Schedule IDS",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="otp",
     *          description="OTP",
     *          required=false,
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
     *              @OA\Schema(example={"status": "success","error":{},"data":{"OK"}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"status": "error","error":{{"type":"danger","text":"Недостаточно денег!"}},"data":{}}
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
    protected function prePaySeveralMonths(PrepaySeveralMonthsRequest $request): void {
        $type             = $request->validated()["type"];   // "CARD" или "ACCOUNT"
        $otp              = $request->validated()["otp"] ?? null;

        // Для Java Prepay:
        $user_id          = $request->validated()["user_id"] ?? Auth::user()->id;
        $contract_id      = $request->validated()["contract_id"] ?? null;
        $card_id          = $request->validated()["card_id"] ?? null;

        // TODO: Протестировать вариант, когда это массив из одного элемента, который является строкой из schedule ids
        $schedule_ids     = $request->validated()["schedule_ids"];

        // Какой-то Legacy Code, который непонятно что делает.
        if ( is_array($schedule_ids) && ( ((int) $schedule_ids[0]) === 0 ) ) {
            $schedule_ids = json_decode($schedule_ids[0]);
        }

        $url_severalmonth = Config::get('test.test');

        $data = [
            "type" => $type,
            "user_id" => $user_id,
            "contract_id" => $contract_id,
            "schedule_ids" => $schedule_ids,
        ];

        if ( $type === "CARD" ) {
            $url_severalmonth = Config::get('test.test');

            $data["card_id"] = $card_id;

            $buyer = Buyer::with("card")->find($user_id);
            $requestPhone = new Request();
            $requestPhone->merge(['phone' => correct_phone($buyer->phone)]);

            if ($request->has('otp')) {
                $requestPhone->merge(['code' => $otp]);
                $sms_response = LoginService::checkSmsCode($requestPhone);
                if ($sms_response['code'] === 0) {
                    BaseService::handleError([ __('auth.error_code_wrong') ]);
                }
            } else {
                $str = EncryptHelper::decryptData($buyer->card->card_number);
                $card_number = '****' . substr($str, -4);
                $msg = "Hurmatli mijoz, sizning " . $card_number . " kartangiz tomonidan muddatidan oldin to'lov qilish uchun tasdiqlash kodi: :code Tel: " . callCenterNumber(2);
                $returned = $this->sendSmsCode($requestPhone, true, $msg);
                if ( $returned["status"] === "success" ) {
                    BaseService::handleResponse($returned);
                }
                BaseService::handleError($returned);
            }
        }

        [$response, $http_code] = $this->makeRequest($url_severalmonth, $data);
        FcmService::notifyIfContractComplete($contract_id);
        $this->prepareResponse($response, $http_code, $type, $otp); // END
    }

    /**
     * @OA\Post(
     *      path="/contract/prepay/several-month/confirm",
     *      tags={"Contract"},
     *      security={{"api_token_security":{}}},
     *      summary="Payment",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="type",
     *          description="Type",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="card_id",
     *          description="Card id",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
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
     *          name="user_id",
     *          description="User ID",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="schedule_ids",
     *          description="Schedule IDS",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="otp",
     *          description="OTP",
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
     *              @OA\Schema(example={"status": "success","error":{},"data":{"OK"}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"status": "error","error":{{"type":"danger","text":"Недостаточно денег!"}},"data":{}}
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

    /**
     * @OA\Post(
     *      path="/contract/prepay/free-pay",
     *      tags={"Contract"},
     *      security={{"api_token_security":{}}},
     *      summary="Payment",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="type",
     *          description="Type",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="card_id",
     *          description="Card id",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
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
     *          name="user_id",
     *          description="User ID",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="amount",
     *          description="Amount",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="otp",
     *          description="OTP",
     *          required=false,
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
     *              @OA\Schema(example={"status": "success","error":{},"data":{"OK"}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"status": "error","error":{{"type":"danger","text":"Недостаточно денег!"}},"data":{}}
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
    protected function prePayFreePay(PrepayFreePayRequest $request): void {
        $type             = $request->validated()["type"];
        $otp              = $request->validated()["otp"] ?? null;

        // Для Java Prepay:
        $user_id          = $request->validated()["user_id"] ?? Auth::user()->id;
        $contract_id      = $request->validated()["contract_id"] ?? null;
        $card_id          = $request->validated()["card_id"] ?? null;
        $amount           = $request->validated()["amount"];

        $url_freepay = Config::get('test.test');

        $data = [
            "type" => $type,
            "user_id" => $user_id,
            "contract_id" => $contract_id,
            "amount" => $amount,
        ];

        if ( $type === "CARD" ) {
            $url_freepay = Config::get('test.test');

            $data["card_id"] = $card_id;

            $buyer = Buyer::with("card")->find($user_id);
            $requestPhone = new Request();
            $requestPhone->merge(['phone' => correct_phone($buyer->phone)]);

            if ($request->has('otp')) {
                $requestPhone->merge(['code' => $otp]);
                $sms_response = LoginService::checkSmsCode($requestPhone);
                if ($sms_response['code'] === 0) {
                    BaseService::handleError([ __('auth.error_code_wrong') ]);
                }
            } else {
                $str = EncryptHelper::decryptData($buyer->card->card_number);
                $card_number = '****' . substr($str, -4);
                $msg = "Hurmatli mijoz, sizning " . $card_number . " kartangiz tomonidan muddatidan oldin to'lov qilish uchun tasdiqlash kodi: :code Tel: " . callCenterNumber(2);
                $returned = $this->sendSmsCode($requestPhone, true, $msg);
                if ( $returned["status"] === "success" ) {
                    BaseService::handleResponse($returned);
                }
                BaseService::handleError($returned);
            }
        }

        [$response, $http_code] = $this->makeRequest($url_freepay, $data);

        FcmService::notifyIfContractComplete($contract_id);
        $this->prepareResponse($response, $http_code, $type, $otp);
    }
    /**
     * @OA\Post(
     *      path="/contract/prepay/free-pay/confirm",
     *      tags={"Contract"},
     *      security={{"api_token_security":{}}},
     *      summary="Payment",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="type",
     *          description="Type",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="card_id",
     *          description="Card id",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
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
     *          name="user_id",
     *          description="User ID",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="amount",
     *          description="Amount",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="otp",
     *          description="OTP",
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
     *              @OA\Schema(example={"status": "success","error":{},"data":{"OK"}})
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request or error message",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={"status": "error","error":{{"type":"danger","text":"Недостаточно денег!"}},"data":{}}
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
}
