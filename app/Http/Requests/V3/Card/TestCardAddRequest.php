<?php

namespace App\Http\Requests\V3\Card;

use App\Rules\CheckCardValidDateV2;
use App\Rules\HashedCardNumberCheckV2;
use App\Services\API\V3\BaseService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class TestCardAddRequest extends FormRequest
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
            'pan' => [
                'required',
                'digits:16',
                new HashedCardNumberCheckV2()
            ],
            'expiry' => [
                'required',
                'digits:4',
                new CheckCardValidDateV2()
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pan' => str_replace(' ', '', $this->pan),
            'expiry' => str_replace('/', '', $this->expiry),
            //'guid' => md5($this->pan)
        ]);
    }

    public function messages()
    {
        return [

        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        BaseService::handleError($validator->errors()->getMessages());
    }
}
