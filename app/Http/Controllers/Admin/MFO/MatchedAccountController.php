<?php

namespace App\Http\Controllers\Admin\MFO;

use App\Http\Controllers\Controller;
use App\Http\Requests\MatchedAccount\InsertMfoMatchRequest;
use App\Http\Requests\MatchedAccount\PageMfoMatchRequest;
use App\Http\Requests\MatchedAccount\UpdateMfoMatchRequest;
use App\Models\MatchedAccount;
use App\Services\API\V3\Account\MatchedAccountService;
use App\Services\API\V3\BaseService;
use Illuminate\Http\JsonResponse;

class MatchedAccountController extends Controller
{
    public MatchedAccountService $service;

    public function __construct(MatchedAccountService $matchedAccountService)
    {
        $this->service = $matchedAccountService;
    }

    public function list(PageMfoMatchRequest $request):JsonResponse
    {
        $list = $this->service->list( $request->limit ?? 10, $request->page ?? 1);
        return BaseService::successJson($list);
    }

    public function insert(InsertMfoMatchRequest $request)
    {
        $new = $this->service->new($request['mfo_mask'],
                                    $request['one_c_mask'],
                                    $request['parent_id'],
                                    $request['number'],
                                    $request['mfo_account_name']);

        return BaseService::successJson($new);
    }

    public function update(UpdateMfoMatchRequest $request, MatchedAccount $accountMatch):JsonResponse
    {
        $update = $this->service->update($request, $accountMatch);
        return BaseService::successJson($update);
    }

    public function delete(MatchedAccount $accountMatch):JsonResponse
    {
         try{
            $accountMatch->delete();
            return BaseService::successJson();
         }catch(\Throwable $exception){
            return BaseService::errorJson(['Can`t Delete Error!']);
        }
    }

}
