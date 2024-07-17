<?php

namespace App\Http\Requests\Core\BuyerController;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PhonesCountRequest extends FormRequest
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
            'buyer_id' => [ 'required', 'exists:users,id' ],
        ];
    }

    public function messages()
    {
        return [
            'buyer_id.required' => 'buyer_id_required',
            'buyer_id.exists'   => 'buyer_not_found',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        $validationErrors = $validator->errors()->messages();

        $result['status'] = 'error';
        $result['response']['code'] = 400;
        $result['response']['message'] = [];
        $result['response']['errors'] = reset($validationErrors);
        $result['data'] = [];

        throw new HttpResponseException(response()->json($result));
    }
}
