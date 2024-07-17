<?php

namespace App\Http\Requests;

use App\Enums\BuyerPersonalsEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class SaveBuyerPersonalPhotoData extends FormRequest
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
            'buyer_id'   => [ 'required', 'integer', 'exists:users,id' ],
            'type'       => [
                'required',
                'string',
                Rule::in(BuyerPersonalsEnum::BUYER_PERSONALS_ENUM["TYPES"])
            ],
            'file'       => [
                'required',
                'image',
                'mimes:bmp,jpe,jpg,jpeg,png,webp',
                'max:35840'
//                'dimensions:min_width=1024,min_height=720',
            ]
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
