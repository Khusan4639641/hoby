<?php

namespace App\Http\Controllers\V3;

use App\Services\API\V3\FcmService;
use Illuminate\Http\Request;

class FcmController extends CoreController
{
    protected FcmService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new FcmService();
    }

    public function updateToken(Request $request)
    {
        return $this->service::updateToken($request);
    }
}
