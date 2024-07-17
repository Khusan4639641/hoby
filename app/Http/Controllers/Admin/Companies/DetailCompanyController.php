<?php

namespace App\Http\Controllers\Admin\Companies;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\API\V3\BaseService;
use App\Services\Company\DetailCompanyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DetailCompanyController extends Controller
{
    public DetailCompanyService $detailCompanyService;

    public function __construct(DetailCompanyService $detailCompanyService)
    {
        $this->detailCompanyService = $detailCompanyService;
    }

    public function list(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|min:3'
        ]);

        if ($validator->fails()) {
            BaseService::handleError($validator->errors()->getMessages());
        }

        $data = $validator->validate();


        BaseService::handleResponse(
            Company::query()
                ->select(['id', 'brand', 'name'])
                ->with('accounts')
                ->where('parent_id', null)
                ->where('status', 1)
                ->where('general_company_id', 3)
                ->when(array_key_exists('search', $data) && strlen($data['search']) > 0, function (Builder $builder) use ($data) {
                    return $builder->where("brand", 'like', '%' . $data['search'] . '%');
                })
                ->paginate(25)
        );
    }

    public function single($id)
    {
        $company = Company::query()
            ->select(['id', 'brand', 'payment_account', 'name', 'mfo', 'uniq_num', 'date_pact'])
            ->where('id', $id)
            ->where('parent_id', null)
            ->where('status', 1)
            ->where('general_company_id', 3)
            ->first();

        if ($company == null) {
            BaseService::handleError(['Merchant not found']);
        } else {
            BaseService::handleResponse(
                $this->detailCompanyService->addHeaderTextForCompany($company)
            );
        }
    }
}
