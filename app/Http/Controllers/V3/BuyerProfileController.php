<?php

namespace App\Http\Controllers\V3;

use App\Services\API\V3\BuyerProfileService;
use Illuminate\Http\Request;

class BuyerProfileController extends CoreController
{
    protected BuyerProfileService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new BuyerProfileService();
    }

    /**
     * @OA\Post(
     *      path="/buyer/verify/modify",
     *      tags={"Buyer"},
     *      security={{"api_token_security":{}}},
     *      summary="Метод добавление изображение паспорта для покупателя (Load passport image for buyer)",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="step",
     *          description="Step",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="integer"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="address_region",
     *          description="Region",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="address_area",
     *          description="Area",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="address",
     *          description="Address",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="passport_selfie",
     *          description="Passport with selfie",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="file"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="passport_first_page",
     *          description="First page of passport",
     *          required=false,
     *          in="query",
     *          @OA\Schema(
     *              type="file"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="passport_with_address",
     *          description="Address page of passport",
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
     *                  "data": {
     *                      "id": 372390,
     *                      "email": null,
     *                      "name": null,
     *                      "surname": null,
     *                      "patronymic": null,
     *                      "gender": null,
     *                      "phone": "+998900626204",
     *                      "birth_date": null,
     *                      "region": null,
     *                      "local_region": null,
     *                      "email_verified_at": null,
     *                      "token_generated_at": "2022-06-03 17:51:56",
     *                      "verify_message": null,
     *                      "status": 10,
     *                      "status_employee": null,
     *                      "company_id": null,
     *                      "seller_company_id": null,
     *                      "created_at": "2022-06-03T06:16:59.000000Z",
     *                      "created_by": null,
     *                      "updated_at": "2022-06-09T05:36:17.000000Z",
     *                      "verified_at": null,
     *                      "verified_by": null,
     *                      "ticketit_admin": 0,
     *                      "ticketit_agent": 0,
     *                      "kyc_status": 3,
     *                      "kyc_id": null,
     *                      "is_saller": 0,
     *                      "device_os": null,
     *                      "lang": null,
     *                      "firebase_token_android": "v2RtRbzHfAtp-CzTqQL9dWN7FC5tq2-YeTLzOBPSno_1CJQcBNbjqgO5jY",
     *                      "firebase_token_ios": null,
     *                      "doc_path": 1,
     *                      "black_list": 0,
     *                      "vip": 0,
     *                      "personals": {
     *                          "id": 364653,
     *                          "user_id": 372390,
     *                          "birthday": null,
     *                          "city_birth": null,
     *                          "work_company": null,
     *                          "work_phone": null,
     *                          "passport_number": null,
     *                          "passport_number_hash": null,
     *                          "passport_date_issue": null,
     *                          "passport_issued_by": null,
     *                          "passport_expire_date": null,
     *                          "passport_type": 6,
     *                          "home_phone": null,
     *                          "pinfl": null,
     *                          "pinfl_hash": null,
     *                          "pinfl_status": 1,
     *                          "inn": null,
     *                          "mrz": null,
     *                          "social_vk": null,
     *                          "social_facebook": null,
     *                          "social_linkedin": null,
     *                          "social_instagram": null,
     *                          "vendor_link": null,
     *                          "created_at": "2022-06-03T06:16:59.000000Z",
     *                          "updated_at": "2022-06-03T06:18:28.000000Z",
     *                          "passport_selfie": null,
     *                          "passport_first_page": null,
     *                          "passport_with_address": null
     *                      }
     *                  }
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
    public function modifyVerification(Request $request)
    {
        return $this->service::modifyVerification($request);
    }
}
