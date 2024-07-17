<?php

namespace App\Http\Controllers\v3;

use App\Http\Controllers\V3\CoreController;
use App\Models\MyIDJob;
use App\Services\API\V3\MyIDService;
use Illuminate\Http\Request;

class MyIDController extends CoreController
{
    public MyIDService $service;

    public function __construct()
    {
        $this->service = new MyIDService();
    }
    public function token()
    {
        return $this->service->token();
    }

    /**
     * @OA\Post(
     *      path="/myid/job",
     *      tags={"MyID"},
     *      security={{"api_token_security":{}}},
     *      summary="MyID get credentials",
     *      description="Return json",
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  example={
     *                      "pass_data": "AA1234567",
     *                      "birth_date": "1990-01-01",
     *                      "agreed_on_terms": true,
     *                      "photo_from_camera": {
     *                          "front": "data:image/jpeg;base64,iVBORw0KGgoAAAANSUhEUgAAA7wAAAM6CAYAAACiqk..."
     *                      },
     *                      "company_id": 12345
     *                  }
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
     *                  "data": {}
     *              })
     *          )
     *       ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad Request",
     *          @OA\MediaType(
     *              mediaType="application/json",
     *                 @OA\Schema(example={"code":0,"error":{{"type":"danger","text":"api.contract_not_found"}},"data":{}})
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
    public function job(Request $request)
    {
        return $this->service->job($request);
    }

    public function jobStatus(Request $request)
    {
        $this->service->jobStatus($request);
    }

    public function report($id)
    {
        $result = MyIDJob::where('user_id',$id)->where('result_code',1)->orderBy('id','DESC')->first();
        if($result){

            return $this->service->handleResponse($result);
        }
        return $this->service->handleError([__('api.buyer_not_found')]);
    }
    public function reportView($id)
    {
        $buyer_id = $id;
        return view('panel.buyer.myid', compact('buyer_id'));
    }
}
