<?php

namespace App\Http\Controllers\Core\Auth\V3;

use App\Http\Controllers\Core\Auth\AuthController;
use App\Http\Response\BaseResponse;
use App\Models\Company;
use App\Models\V3\UserV3;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class LoginV3Controller extends AuthController
{
    /**
     * @OA\Post (
     *     path="/api/v3/billing/login",
     *     description="billing login",
     *     tags={"login"},
     *     @OA\Parameter(
     *      in="query",
     *      name="partner_id",
     *      @OA\Schema(type="integer"),
     *      required=true,
     *      description="partner_id",
     *     ),
     *     @OA\Parameter(
     *     name="password",
     *     in="query",
     *     @OA\Schema(type="string"),
     *     required=true,
     *     description="password",
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ok",
     *         @OA\MediaType(
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
     *                              "id": 999999,
     *                              "user_status": 5,
     *                              "api_token": "3y8mdJg1mvCo52ktwDqASZSw3Ocykcx7g2d5ptIbfL32wx510dIJfuGcrBh1"
     *                              },
     *                          },
     *                     }
     *                 )
     *         ),
     *     ),
     *      @OA\Response(
     *          response=422,
     *          description="Unauthenticated",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                             "message": {"The given data was invalid."},
     *                             "error": {
     *                                  "partner_id": {"partner id maydoni uchun tanlangan qiymat noto`g`ri."}
     *                               },
     *                     }
     *                 )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Internal Server Error",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         {"status": "error",
     *                         "response": {
     *                                 "code": 500,
     *                                 "message": "password not correct",
     *                                 "error": {},
     *                          },
     *                          "data": {
     *                              "id": 999999,
     *                              "user_status": 1,
     *                              },
     *                          },
     *                     }
     *                 ),
     *          ),
     *     ),
     * )
     *
     */
    public function BillingLogin(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'partner_id' => ['required', 'exists:companies,id', 'integer',],
            'password'   => ['required'],
        ]);

        $user = Company::find($request->get('partner_id'))->user;

        $data = [
            'id'          => $user->id,
            'user_status' => $user->status,
        ];
        if (Hash::check($request->password, $user->password)) {
            $token = Str::random(60);
            Redis::set("User:{$token}", $user->id, "ex", 600);
            Auth::login($user);
            $data['api_token'] = $token;

            return BaseResponse::success($data);
        }
        return BaseResponse::error($data, 500, 'password not correct');
    }


    /**
     * @OA\Post (
     *     path="/api/v3/cabinet/login",
     *     description="cabinet login",
     *     tags={"login"},
     *     @OA\Parameter(
     *      in="query",
     *      name="phone",
     *      @OA\Schema(type="integer"),
     *      required=true,
     *      description="phone",
     *     ),
     *     @OA\Parameter(
     *     name="password",
     *     in="query",
     *     @OA\Schema(type="string"),
     *     required=true,
     *     description="password",
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ok",
     *         @OA\MediaType(
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
     *                              "id": 999999,
     *                              "user_status": 5,
     *                              "api_token": "3y8mdJg1mvCo52ktwDqASZSw3Ocykcx7g2d5ptIbfL32wx510dIJfuGcrBh1"
     *                              },
     *                          },
     *                     }
     *                 )
     *         ),
     *     ),
     *      @OA\Response(
     *          response=422,
     *          description="Unauthenticated",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                             "message": {"The given data was invalid."},
     *                             "error": {
     *                                  "partner_id": {"partner id maydoni uchun tanlangan qiymat noto`g`ri."}
     *                               },
     *                     }
     *                 )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=500,
     *          description="Internal Server Error",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(
     *                     example={
     *                         {"status": "error",
     *                         "response": {
     *                                 "code": 500,
     *                                 "message": "password not correct",
     *                                 "error": {},
     *                          },
     *                          "data": {
     *                              "id": 999999,
     *                              "user_status": 1,
     *                              },
     *                          },
     *                     }
     *                 ),
     *          ),
     *     ),
     * )
     *
     */
    public function cabinetLogin(Request $request)
    {
        $credentials = $request->validate([
            'phone'    => ['required', 'exists:users,phone', 'string',],
            'password' => ['required'],
        ]);

        $user = UserV3::where('phone', $request->get('phone'))->first();

        $data = [
            'id'          => $user->id,
            'user_status' => $user->status,
        ];
        if (Hash::check($request->password, $user->password)) {
            $token = Str::random(60);
            Redis::set("User:{$token}", $user->id, "ex", 600);
            Auth::login($user);
            $data['api_token'] = $token;

            return BaseResponse::success($data);
        }
        return BaseResponse::error($data, 500, 'password not correct');
    }


}
