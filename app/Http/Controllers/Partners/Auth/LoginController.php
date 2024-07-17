<?php

namespace App\Http\Controllers\Partners\Auth;

use App\Http\Controllers\V3\CoreController;
use App\Services\API\V3\Partners\AuthService;
use Illuminate\Http\Request;

class LoginController extends CoreController
{
    protected AuthService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new AuthService();
    }

    /**
     * @OA\Post(
     *      path="/auth",
     *      tags={"Authorization"},
     *      summary="Метод для авторизации (Authorization partner by partner_id and password)",
     *      description="Return json",
     *      @OA\Parameter(
     *          name="password",
     *          description="Password",
     *          required=true,
     *          in="query",
     *          @OA\Schema(
     *              type="string"
     *          )
     *      ),
     *      @OA\Parameter(
     *          name="partner_id",
     *          description="ID of partner",
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
     *              @OA\Schema(example={
     *                  "status": "success",
     *                  "error": {},
     *                  "data": {
     *                      "user_status": 1,
     *                      "user_id": 235277,
     *                      "api_token": "990dfe56226781128aef46ee8f319924"
     *                  }
     *              })
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
     *                              "text": "Parol noto`g`ri "
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
    public function auth(Request $request)
    {
        $this->service->validateForm($request);
        return $this->service->auth($request);
    }
}
