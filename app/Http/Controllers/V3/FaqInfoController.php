<?php

namespace App\Http\Controllers\V3;


use App\Http\Requests\PageFaqRequest;
use App\Services\API\V3\BaseService;
use App\Services\FaqService;
use Illuminate\Http\Exceptions\HttpResponseException;

class FaqInfoController extends CoreController
{
    public FaqService $service;

    public function __construct(FaqService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function show(PageFaqRequest $request):HttpResponseException
    {
        if ( $faq = $this->service->show($request) ) {
            BaseService::handleResponse($faq);
        }
        BaseService::handleError(['Not found']);
    }
}
