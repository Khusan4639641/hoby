<?php

namespace App\Http\Requests\Collector\Transaction;

use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [];
    }

    public function rules(): array
    {
        return [
            'collector_contract_id' => 'required|exists:collector_contract,id',
            'type'                  => 'required',
            'content'               => 'required'
        ];
    }

}
