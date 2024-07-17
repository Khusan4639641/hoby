<?php

namespace App\Http\Requests;

use App\Rules\CheckCardValidDate;
use App\Rules\HashedCardNumberCheck;
use click\requests\Request;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class FakeTransactionExportRequest extends FormRequest
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
            'data'=>['required','array']
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        $errors = [];
        foreach($validator->errors()->messages() as $error)
            array_push($errors, $error[0]);

        $result = [
            'status' => 'error',
            'response' => [
                'code' => '',
                'message' => [],
                'errors' => $errors
            ],
            'data' => [],
        ];

        throw new HttpResponseException(response()->json($result));
    }
}
