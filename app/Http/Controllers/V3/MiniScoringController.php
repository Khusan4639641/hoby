<?php

namespace App\Http\Controllers\v3;

use App\Http\Controllers\V3\CoreController;
use App\Services\API\V3\MiniScoringService;
use App\Services\API\V3\resus\MyIDService;
use Illuminate\Http\Request;

class MiniScoringController extends CoreController
{
    public MiniScoringService $service;
    public MyIDService $myIdService;

    public function __construct()
    {
        $this->service = new MiniScoringService();
    }

    public function init()
    {
        return $this->service->init();
    }

    public function checkStatus(Request $request)
    {
        $myid_result = $this->myIdService->job($request);

        if ($myid_result['status']['errors']) {

        }


        return $this->service->checkStatus();
    }
}
