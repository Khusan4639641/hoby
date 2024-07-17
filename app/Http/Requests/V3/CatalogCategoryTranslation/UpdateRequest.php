<?php

namespace App\Http\Requests\V3\CatalogCategoryTranslation;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateRequest extends FormRequest
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
            'title' => 'required|string'
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $this->handleError($validator->errors()->getMessages());
    }

    private function handleError($messages = [],  $status = 'error', $status_code = 400)
    {
        throw new HttpResponseException(
            response()->json([
                'status' => $status,
                'error' => $this->beutifyMessage($messages),
                'data' => [],
            ], $status_code)
        );
    }

    private function beutifyMessage($messages = [])
    {
        $errors = [];
        if (!empty($messages)) {
            foreach ($messages as $key => $message) {
                if (is_array($message)) {
                    self::beutifyMessage($message);
                }
                $errors[] = [
                    'type' => 'danger',
                    'text' => is_array($message) ? $message[0] : $message
                ];
            }
        }
        return $errors;
    }
}
