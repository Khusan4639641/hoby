<?php

namespace App\Http\Requests\DebtCollect;

use App\Rules\CheckAllowedDebtorsToCollector;
use Illuminate\Foundation\Http\FormRequest;

class AttachDebtorRequest extends FormRequest
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
            'debtor_id' => [
                'required',
                'array',
                new CheckAllowedDebtorsToCollector($this->route('debt_collector')),
            ],
        ];
    }
}
