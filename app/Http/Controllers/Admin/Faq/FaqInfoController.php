<?php

namespace App\Http\Controllers\Admin\Faq;

use App\Http\Controllers\Core\CoreController;
use App\Http\Requests\AddFaqRequest;
use App\Http\Requests\PageFaqRequest;
use App\Http\Requests\UpdateFaqRequest;
use App\Models\FaqInfo;
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

    public function faqList(PageFaqRequest $request):HttpResponseException
    {
        if ( $faq = $this->service->list($request) ) {
            BaseService::handleResponse($faq);
        }
        BaseService::handleError(['Not found']);
    }

    public function showHistory(PageFaqRequest $request):HttpResponseException
    {
        if ( $faq = $this->service->history($request) ) {
            BaseService::handleResponse($faq);
        }
        BaseService::handleError(['History not found']);
    }

    public function insert(AddFaqRequest $request):HttpResponseException
    {
        if ($faq = $this->service->new($request) ) {
            BaseService::handleResponse($faq);
        }
        BaseService::handleError(['Insert error']);
    }

    public function update(UpdateFaqRequest $request, FaqInfo $faqInfo):HttpResponseException
    {
        if ($faq = $this->service->update($request, $faqInfo)) {
            BaseService::handleResponse($faq);
        }
        BaseService::handleError(['Update error']);
    }

    public function delete(FaqInfo $faqInfo):HttpResponseException
    {
        if ($faqInfo->history()->delete() || $faqInfo->delete()) {
            BaseService::handleResponse();
        }
        BaseService::handleError(['Delete error']);
    }
}
