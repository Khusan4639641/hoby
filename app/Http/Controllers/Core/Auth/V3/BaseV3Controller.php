<?php

namespace App\Http\Controllers\Core\Auth\V3;

use App\Http\Controllers\Controller;

class BaseV3Controller extends Controller
{
    /**
     * @OA\Info(
     *      version="3.0.1",
     *      title="REST API test Documentation",
     *      description="Documentation for developoers,structure,request,response etc..",
     *      @OA\Contact(
     *          email="info@test.xyz"
     *      ),
     *      @OA\License(
     *          name="Go to version 1.0.0",
     *          url="/api/documentation",
     *      ),
     * )
     * @OAS\SecurityScheme(
     *      securityScheme="bearer",
     *      type="http",
     *      scheme="bearer"
     * )
     * @OA\Server(
     *      url=L5_SWAGGER_CONST_HOST2,
     *      description="REST API test Service V3"
     * )
     *
     */

}
