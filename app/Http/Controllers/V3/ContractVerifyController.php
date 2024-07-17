<?php

namespace App\Http\Controllers\V3;

use App\Http\Requests\ContractVerifyRequest;
use App\Models\Contract;
use App\Services\API\V3\BaseService;
use App\Services\API\V3\ContractVerifyService;
use Illuminate\Http\Request;

class ContractVerifyController extends CoreController
{
    public function list(Request $request, ContractVerifyService $service)
    {
        return $service->list($request);
    }

    public function verify(ContractVerifyRequest $request, ContractVerifyService $service)
    {
        $contract = Contract::where('id', $request->contract_id)->select('company_id', 'general_company_id', 'period')->first();
        if ($contract->company->isresus() && ($contract->generalCompany->is_mfo === 0 || $contract->period === 3)) {
            BaseService::handleError(['This contract is not allowed to be verified']);
        }
        return $service->verify($request);
    }
}
