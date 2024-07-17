<?php

namespace App\Http\Requests;

use App\Services\API\V3\BaseService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

class OrderListV3Request extends FormRequest
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
            'status'                => ['sometimes','array'],
            'status.*'              => ['required','integer'],
            'cancellation_status'   => ['sometimes',Rule::in(1)],
            'partner_id'            => ['sometimes','exists:users,id'],
            'per_page'              => ['sometimes','integer'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        BaseService::handleError($validator->errors()->getMessages());
    }
}
