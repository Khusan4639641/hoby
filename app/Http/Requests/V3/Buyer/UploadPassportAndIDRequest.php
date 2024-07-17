<?php

namespace App\Http\Requests\V3\Buyer;

use App\Services\API\V3\BaseService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class UploadPassportAndIDRequest extends FormRequest
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
            'passport_selfie' => ['requiredIf:passport_type,6', 'image'],
            'passport_first_page' => ['nullable', 'image'],
            'passport_with_address' => ['nullable', 'image'],
            'id_selfie' => ['requiredIf:passport_type,0', 'image'],
            'id_first_page' => ['nullable', 'image'],
            'id_second_page' => ['nullable', 'image'],
            'id_with_address' => ['nullable', 'image'],
            'buyer_id' => ['nullable', 'exists:users,id'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        BaseService::handleError($validator->errors()->getMessages());
    }

}
