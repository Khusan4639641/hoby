<?php

namespace App\Http\Requests\Collector\Contract;

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
          'region'       => 'integer',
          'local_region' => 'integer',
          'page'         => 'integer|min:1'
        ];
    }

}
