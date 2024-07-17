<?php

namespace app\Http\Controllers\Admin\Companies;

use App\Http\Controllers\Controller;
use App\Services\API\V3\BaseService;
use App\Services\Company\CompanyAccountService;
use Illuminate\Http\Request;

class CompanyAccountController extends Controller
{
    protected $service;

    public function __construct(CompanyAccountService $service)
    {
        $this->service = $service;
    }

    public function store(Request $request)
    {
        $validator = \Validator::make(
            $request->all(),
            [
                'company_id' => 'required|int|exists:companies,id',
                'name' => 'required|string|max:255',
                'payment_account' => 'required|string|digits:20',
                'mfo' => 'required|string|digits:5'
            ]
        );

        if ($validator->fails()) {
            BaseService::handleError($validator->errors()->getMessages());
        }

        $data = $validator->validate();

        BaseService::handleResponse(
            $this->service->create(
                $data['company_id'],
                $data['name'],
                $data['payment_account'],
                $data['mfo']
            )
        );
    }

    public function update(int $id, Request $request)
    {
        $validator = \Validator::make(
            array_merge(
                $request->all(),
                ["id" => $id]
            ),
            [
                'id' => 'required|int|exists:company_accounts,id',
                'name' => 'required|string|max:255',
                'payment_account' => 'required|string|digits:20',
                'mfo' => 'required|string|digits:5'
            ]
        );

        if ($validator->fails()) {
            BaseService::handleError($validator->errors()->getMessages());
        }

        $data = $validator->validate();

        BaseService::handleResponse(
            $this->service->update(
                $data['id'],
                $data['name'],
                $data['payment_account'],
                $data['mfo']
            )
        );
    }

    public function delete($id, Request $request)
    {
        $validator = \Validator::make(
            ["id" => $id],
            [
                'id' => 'required|int|exists:company_accounts,id',
            ]
        );

        if ($validator->fails()) {
            BaseService::handleError([
                'text' => 'шаблон не найден',
            ],'error',404);
        }

        $data = $validator->validate();

        BaseService::handleResponse(
            $this->service->delete($data['id']),
        );
    }
}
