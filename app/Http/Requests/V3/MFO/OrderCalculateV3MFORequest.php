<?php

namespace App\Http\Requests\V3\MFO;

use App\Services\API\V3\BaseService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class OrderCalculateV3MFORequest extends FormRequest
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
            'user_id'           => 'exists:users,id|exists:buyer_settings,user_id',
            'products'          => 'required|array',
            'products.*.amount' => 'required|integer',
            'products.*.price'  => 'required|integer',
            'products.*.product_id'  => 'nullable|integer',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        BaseService::handleError($validator->errors()->getMessages());
    }
}
