<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Exceptions\HttpResponseException;

class SaveContractUrl extends FormRequest
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
            'data.*.contract_id'            => 'required|numeric',
            'data.*.url'                    => 'nullable|url',
            'data.*.contract_summa'         => 'required|numeric|regex:/^[1-9]\d{0,15}(?:\.\d{1,2})?$/',  // decimal(16.2) or integer
            'data.*.contract_created_at'    => 'required|integer'     //2022-05-20T10:13:38
        ];
    }

//    /**
//     * Get the error messages for the defined validation rules.
//     *
//     * @return array
//     */
//    public function messages() {}
//    Для языковых сообщений ошибок валидации, смотрите в lang/xx/validation.php

    protected function failedValidation(Validator $validator): void
    {
//        dd(app()->getLocale());
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

//{
//    "contract_id": 123456,
//    "url": 123456,
//    "contract_summa": 123456,
//    "contract_created_at": 123456,
//}
