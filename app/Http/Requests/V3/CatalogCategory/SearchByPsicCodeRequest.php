<?php

namespace App\Http\Requests\V3\CatalogCategory;

use Illuminate\Foundation\Http\FormRequest;

class SearchByPsicCodeRequest extends FormRequest
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
            'psic_code' => ['required', 'min:5']
        ];
    }
}
