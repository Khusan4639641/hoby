<?php

namespace App\Http\Controllers\Partners\MFO;

use App\Http\Controllers\V3\CoreController;
use App\Services\API\V3\BaseService;
use App\Services\MFO\MFOAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends CoreController
{

    public MFOAuthService $authService;

    public function __construct()
    {
        parent::__construct();
        $this->authService = new MFOAuthService;
    }

    public function validateLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id',
        ]);
        if ($validator->fails()) {
            BaseService::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public function validateVerifyLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'contract_id' => 'required|exists:contracts,id',
            'code' => 'required|numeric|digits:6',
        ]);
        if ($validator->fails()) {
            BaseService::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();
    }

    public function login(Request $request)
    {
        $validated = $this->validateLogin($request);
        $this->authService->login($validated['contract_id']);
    }

    public function verifyLogin(Request $request)
    {
        $validated = $this->validateVerifyLogin($request);
        $response = $this->authService->verifyLogin($validated['contract_id'], $validated['code']);
        return BaseService::handleResponse($response);
    }

}
