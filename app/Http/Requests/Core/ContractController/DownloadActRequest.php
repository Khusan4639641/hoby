<?php

namespace App\Http\Requests\Core\ContractController;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class DownloadActRequest extends FormRequest
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
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation() : void
    {
        if ($this->contract_id) {
            $this->merge([      // Убираем всё кроме цифр
                'contract_id' => (int) preg_replace("/\D+/", "", trim($this->contract_id) ),
            ]);
        }
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'contract_id'     => ['exists:App\Models\Contract,id'],
        ];
    }

    protected function failedValidation(Validator $validator) : void
    {
        $errors = [];
        foreach($validator->errors()->messages() as $error) {
            $errors[] = $error[0];
        }

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
