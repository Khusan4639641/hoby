<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ScoringUniversalRequest extends FormRequest
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
            'date_start' => 'required|before:date_end',
            'date_end'   => 'required|before_or_equal:' . date('Y-m-d')
        ];
    }

    public function messages()
    {
        return [
            'date_start.required'      => __('panel/buyer.date_start_is_required'),
            'date_start.before'        => __('panel/buyer.not_valid_date_start'),
            'date_end.required'        => __('panel/buyer.date_end_is_required'),
            'date_end.before_or_equal' => __('panel/buyer.not_valid_date_end')
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = [];
        foreach($validator->errors()->messages() as $error)
            array_push($errors, $error[0]);

        $json = [
            'message' => 'The given data was invalid.',
            'status'  => 'error',
            'errors'  => $errors
        ];

        throw new HttpResponseException(response()->json($json));
    }
}
