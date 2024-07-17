<?php

namespace App\Http\Controllers\V3;

use App\Models\PartnerSetting;
use App\Models\V3\UserV3;
use App\Services\API\V3\BaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ConfigController extends CoreController
{

  /**
   * @OA\Get(
   *      path="/api/v3/config/app",
   *      tags={"Config"},
   *      security={{"api_token_security":{}}},
   *      summary="Метод для получения конфигурационной информации",
   *      description="Return json",
   *      @OA\Response(
   *          response=200,
   *          description="Successful operation",
   *          @OA\MediaType(
   *              mediaType="application/json",
   *              @OA\Schema(
   *                          example={
   *                                  {
   *                                      "status": "success",
   *                                      "error": {},
   *                                      "data": {
   *                                      "allowedMethod": {"name"},
   *                                      "is_trustworthy": 0
   *                                      }
   *                                  }
   *              })
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

  public function app(): JsonResponse
  {
    $data = [];
    if (Auth::user()) {
      $user = UserV3::find(Auth::user()->id);
      $data['allowedMethod'] = $user->roles->permissions()->whereNotNull('route_name')->pluck('route_name')->all();

      $partnerSetting = PartnerSetting::where('company_id', $user->company_id)->first();
      $is_trustworthy = 0;
      if ($partnerSetting) {
        if (isset($partnerSetting->is_trustworthy)) {
          $is_trustworthy = $partnerSetting->is_trustworthy ? 1 : 0;
        }
      }
      $data['is_trustworthy'] = $is_trustworthy;
    }

    return BaseService::handleResponse($data);
  }
}
