<?php

namespace App\Http\Controllers\V3\Panel;

use App\Http\Controllers\Controller;
use App\Services\API\V3\BaseService;
use App\Services\MFO\AccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PanelController extends Controller
{
    public function generateAccountWithControlKey(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'mask' => 'required|numeric',
            'client_code' => 'required|numeric',
            'currency_code' => 'required|numeric',
            'index_number' => 'nullable|numeric',
        ]);
        if($validator->fails()){
            BaseService::handleError($validator->errors()->messages());
        }

        $inputs = $validator->validated();
        $service = new AccountService();
        $key = $service->calculateControlKey($inputs['client_code'].$inputs['mask'].$inputs['currency_code'].$inputs['client_code'].$inputs['index_number']);
        $account = $inputs['mask'].$inputs['currency_code'].$key.$inputs['client_code'].$inputs['index_number'];
        return BaseService::handleResponse(['account' => $account]);
    }
}
