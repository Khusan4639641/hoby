<?php

namespace App\Http\Requests\V3\CatalogCategoryController;

use Illuminate\Foundation\Http\FormRequest;

class GetCategoriesHierarchyRequest extends FormRequest
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
            'search_value' => ['required', 'string', 'min:2'],
            'limit' => ['required', 'integer', 'between:1,50'],
            'offset' => ['required', 'integer'],
        ];
    }
}
