<?php

namespace App\Http\Requests\V3\User;

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
            'role_id'         => 'required|exists:roles,id',
        ];
    }

}
