<?php

namespace App\Http\Requests\Core\PartnerBuyerController;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddRequest extends FormRequest
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
            'phone' => [ 'required', 'string', 'max:15', 'regex:/^\+?[0-9]+$/' ],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $response['status'] = 'error';
        $response['response']['errors'] = $validator->errors()->messages();

        throw new HttpResponseException(response()->json($response));
    }
}
