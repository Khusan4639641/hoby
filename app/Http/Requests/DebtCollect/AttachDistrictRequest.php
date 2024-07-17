<?php

namespace App\Http\Requests\DebtCollect;

use Illuminate\Foundation\Http\FormRequest;

class AttachDistrictRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
          'district_id.*' => 'unique:debt_collector_district,district_id'
        ];
    }

}
