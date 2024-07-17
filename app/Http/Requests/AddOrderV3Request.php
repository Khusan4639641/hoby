<?php

namespace App\Http\Requests;

use App\Services\API\V3\BaseService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class AddOrderV3Request extends FormRequest
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
            'user_id'           => 'required|exists:users,id',
            'partner_id'        => 'nullable|exists:users,id',
            'period'            => ['required',Rule::in(3,6,9,12,24)],
            'products'          => 'required|array',
            'products.*.amount' => 'required|integer',
            'products.*.price'  => 'required|integer',
            'products.*.name'   => 'required|string',
            'products.*.label'   => 'nullable|string|max:21',
            'products.*.imei'   => 'nullable|numeric|digits:15',
            'products.*.category' => 'required|exists:catalog_categories,id',
            'products.*.original_name'   => 'nullable|string',
            'products.*.original_imei'   => 'nullable|numeric|digits:15',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        BaseService::handleError($validator->errors()->getMessages());
    }
}
