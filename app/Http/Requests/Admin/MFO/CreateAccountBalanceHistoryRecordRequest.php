<?php

namespace App\Http\Requests\Admin\MFO;

use App\Models\MFOAccount;
use App\Services\API\V3\BaseService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateAccountBalanceHistoryRecordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'mfo_account_id' => ['required', 'integer', Rule::exists(MFOAccount::class, 'id')],
            'balance' => ['required', 'integer'],
            'operation_date' => ['required', 'date', 'date_format:Y-m-d', 'before_or_equal:today'],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        return BaseService::handleError($validator->errors()->messages(), 'error', 422);
    }
}
