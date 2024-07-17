<?php

namespace App\Http\Requests;

use App\Rules\CheckCardValidDate;
use App\Rules\HashedCardNumberCheck;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendSmsCodeUniversalRequest extends FormRequest
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
            'card' => ['required', new HashedCardNumberCheck()],
            'exp'  => ['required', new CheckCardValidDate()],
        ];
    }

    public function messages()
    {
        return [
            'card.required' => 'card_number_is_required',
            'exp.required'  => 'expiration_date_is_required',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        $validationErrors = $validator->errors()->messages();
        $errors = [];

        foreach ($validationErrors as $error) {
            array_push($errors, $error[0]);
        }

        $result['status'] = 'error';
        $result['info'] = $errors;

        throw new HttpResponseException(response()->json($result));
    }
}


