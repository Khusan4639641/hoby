<?php

namespace App\Http\Requests\DebtCollect;

use Illuminate\Foundation\Http\FormRequest;

class ExportDebtorByDistrictRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }


    public function rules(): array
    {
        return [
            'district_id' => 'required|int|exists:districts,id',
            'collector_id' => 'required|int|exists:users,id'
        ];
    }

}
