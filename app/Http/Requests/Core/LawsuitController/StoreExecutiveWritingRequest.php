<?php

namespace App\Http\Requests\Core\LawsuitController;

use App\Models\ExecutiveWriting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\RequiredIf;

class StoreExecutiveWritingRequest extends FormRequest
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
            'contract_id' => ['required', 'exists:contracts,id', 'unique:executive_writings,contract_id'], // TODO условие unique нужно будет убрать, если на 1 контракт будет более 1 письма
            'registration_number' => ['required', 'max:30'],
        ];
    }

    public function messages()
    {
        return [
            'contract_id.unique' => 'record by contract_id ' . $this->contract_id . ' already exists',
        ];
    }
}
