<?php

namespace App\Http\Controllers\Admin\Payments;

use App\Http\Controllers\Controller;
use App\Models\DetailPayment;
use App\Services\API\V3\BaseService;
use App\Services\Payment\DetailPaymentService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DetailPaymentController extends Controller
{
    public DetailPaymentService $paymentService;


    public function __construct() {
        $this->paymentService = new DetailPaymentService;
    }

    public function makeTransactionValidate(Request $request) {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer|exists:companies,id',
            'amount' => 'required|integer|min:1',
            'account' => 'required|numeric|digits:20',
            'mfo' => 'required|numeric|digits:5',
            'name' => 'required|string',
            'detail' => 'required|string',
        ]);
        if ($validator->fails()) {
            BaseService::handleError($validator->errors()->getMessages());
        }
        return $validator->validated();

    }

    public function make(Request $request):HttpResponseException {
        $validated = $this->makeTransactionValidate($request);
        $response = $this->paymentService->makeDetailTransaction(
            $validated['company_id'],
            $validated['amount'],
            $validated['account'],
            $validated['mfo'],
            $validated['name'],
            $validated['detail'],
        );
        if($response['status'] === 'error') {
            BaseService::handleError([$response['message']]);
        }
        BaseService::handleResponse();
    }

    public function list():HttpResponseException {
        BaseService::handleResponse(DetailPayment::orderBy('created_at', 'desc')->paginate(10));
    }

    public function config():HttpResponseException {
        BaseService::handleResponse($this->paymentService->getConfig());
    }

}
