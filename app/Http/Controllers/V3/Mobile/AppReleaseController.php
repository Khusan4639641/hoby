<?php

namespace App\Http\Controllers\V3\Mobile;

use App\Http\Controllers\V3\CoreController;
use App\Services\Mobile\AppReleaseVersion\AppReleaseVersionService;
use Illuminate\Http\Request;

class AppReleaseController extends CoreController
{
    protected AppReleaseVersionService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new AppReleaseVersionService();
    }

    /**
     * @param Request $request
     * @return array
     */

    public function getReleaseVersion(Request $request)
    {

        $data = $this->service::getAppReleaseStatus($request);
        return $this->service::handleResponse(['status' => $data]);

    }
}
