<?php

namespace App\Http\Requests;

use App\Rules\CheckCardByPhone;
use App\Rules\CheckCardValidDate;
use App\Rules\HashedCardNumberCheck;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CardAddRequest extends FormRequest
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
            'card_number' => [
                'required',
                new HashedCardNumberCheck(),
                new CheckCardByPhone()
            ],
            'card_valid_date' => [
                'required',
                new CheckCardValidDate()
            ]
        ];
    }

    public function messages()
    {
        return [
            'card_number.required' => __('panel/buyer.card_number_is_required'),
            'card_valid_date.required' => __('panel/buyer.card_valid_date_is_required'),
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
//        $result['response']['code'] = '';
//        $result['response']['message'] = [];
        $result['response']['errors'] = $errors;
//        $result['data'] = [];

        throw new HttpResponseException(response()->json($result));
    }
}
