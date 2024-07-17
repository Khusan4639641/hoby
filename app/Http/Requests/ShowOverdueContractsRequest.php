<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ShowOverdueContractsRequest extends FormRequest
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
            "buyer_id" => ["required", "integer", "exists:users,id"],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            "buyer_id.required" => __("panel/buyer.buyer_id_not_found"),
            "buyer_id.exists"   => __("panel/buyer.err_buyer_not_found"),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $errors = [];
        foreach($validator->errors()->messages() as $error)
            $errors[] = $error[0];

        $json = [
            'status'  => 'error',
            'errors'  => $errors
        ];
        throw new HttpResponseException(response()->json($json));
    }
}
