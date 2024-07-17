<?php

namespace App\Http\Requests\Web\Panel\ReportsController;

use Illuminate\Foundation\Http\FormRequest;

class DebtCollectorsFilteredExportRequest extends FormRequest
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
            'recovery' => [],
            'contract_date_from' => [],
            'contract_date_to' => [],
            'delay_days_from' => [],
            'delay_days_to' => [],
            'contract_balance_from' => [],
            'contract_balance_to' => [],
            'region_id' => [],
        ];
    }
}
