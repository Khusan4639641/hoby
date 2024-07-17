<?php

namespace App\Http\Requests\DebtCollect;

use Illuminate\Foundation\Http\FormRequest;

class StoreContractActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
          'type' => 'required'
        ];
    }

}
