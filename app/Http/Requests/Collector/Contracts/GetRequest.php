<?php

namespace App\Http\Requests\Collector\Contracts;

use Illuminate\Foundation\Http\FormRequest;

class GetRequest extends FormRequest
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
          'local_region_id' => 'integer'
        ];
    }

}
