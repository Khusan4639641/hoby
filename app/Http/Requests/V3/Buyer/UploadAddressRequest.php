<?php

namespace App\Http\Requests\V3\Buyer;

use App\Services\API\V3\BaseService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UploadAddressRequest extends FormRequest
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
            'passport_type' => ['required', Rule::in(6, 0)],
            'passport_first_page' => ['requiredIf:passport_type,6', 'image'],
            'passport_with_address' => ['requiredIf:passport_type,6', 'image'],
            'id_first_page' => ['requiredIf:passport_type,0', 'image'],
            'id_second_page' => ['requiredIf:passport_type,0', 'image'],
            'id_with_address' => ['requiredIf:passport_type,0', 'image'],
            'buyer_id' => ['nullable', 'integer'],
            'hash' => ['nullable','string'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        BaseService::handleError($validator->errors()->getMessages());
    }

}
