<?php

namespace App\Http\Requests\Collector\KatmRegion;

use Illuminate\Foundation\Http\FormRequest;

class AttachRequest extends FormRequest
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
          'katm_region_id' => 'required|exists:katm_regions,id'
        ];
    }

}
