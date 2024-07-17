<?php

namespace App\Http\Requests\V3\Role;

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
            'name'         => 'required|unique:roles,name',
            'display_name' => 'string',
            'description'  => 'string',
        ];
    }

}
