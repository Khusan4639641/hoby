<?php

namespace App\Http\Requests;

use App\Rules\CheckByPhonePrefixCode;
use App\Rules\CheckGuarantPhone;
use App\Rules\CompareBuyersPhoneToGuarants;
use App\Rules\CompareIfArrayElementsAreUnique;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateFaqRequest extends FormRequest
{
    public function rules()
    {
        return [
            'answer_ru' => 'sometimes|required',
            'answer_uz' => 'sometimes|required',
            'status'    => 'sometimes|required|in:0,1',
            'sort'      => 'sometimes|required|integer|min:1',
            'limit'     => 'sometimes|required|integer|max:100',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $errors = [];
        foreach($validator->errors()->messages() as $error) {
            $errors[] = $error[0];
        }

        $json = [
            'status'  => 'error',
            'errors'  => $errors
        ];
        throw new HttpResponseException(response()->json($json));
    }
}
