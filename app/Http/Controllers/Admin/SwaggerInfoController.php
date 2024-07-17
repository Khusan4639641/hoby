<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SwaggerInfoController extends Controller
{
    /**
     * @OA\Info(
     *      version="3.0.0",
     *      title="REST API test Admin Documentation",
     *      description="Documentation for developers,structure,request,response etc..",
     *      @OA\Contact(
     *          email="info@test.xyz"
     *      ),
     * )
     */
}
