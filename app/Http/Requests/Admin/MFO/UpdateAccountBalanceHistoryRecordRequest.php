<?php

namespace App\Http\Requests\Admin\MFO;

use App\Services\API\V3\BaseService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountBalanceHistoryRecordRequest extends FormRequest
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
            'earliest_balance' => ['required', 'integer'],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        return BaseService::handleError($validator->errors()->messages(), 'error', 422);
    }
}
