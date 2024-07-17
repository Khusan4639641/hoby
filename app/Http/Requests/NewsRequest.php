<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NewsRequest extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'date' => 'required',

            'uz_title' => 'required',
            'uz_slug' => 'required',
            'uz_preview_text' => 'required',
            'uz_detail_text' => 'required',

            'ru_title' => 'required',
            'ru_slug' => 'required',
            'ru_preview_text' => 'required',
            'ru_detail_text' => 'required',
        ];
    }
}
