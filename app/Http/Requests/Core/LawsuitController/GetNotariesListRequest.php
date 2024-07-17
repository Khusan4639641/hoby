<?php

namespace App\Http\Requests\Core\LawsuitController;

use App\Services\API\V3\BaseService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GetNotariesListRequest extends FormRequest
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
            'contract_id' => [ 'required', 'exists:contracts,id' ]
        ];
    }

    public function messages()
    {
        return [
            'contract_id.required' => 'Contract_id is required',
            'contract_id.exists' => 'Contract by id ' . $this->contract_id . ' not found',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        BaseService::handleError([$validator->errors()->first()]);
    }
}
