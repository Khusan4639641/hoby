<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Exceptions\HttpResponseException;

class SaveInvoiceNumber extends FormRequest
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
    public function rules(): array
    {
        return [
            'user_id_invoice'                   => 'required|numeric',
            'contract_id_invoice'               => 'required|numeric',
            'fix_inv_number'                    => ['required', 'digits_between:14,20'],
            'percent_inv_number'                => ['required', 'digits_between:14,20'],
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
