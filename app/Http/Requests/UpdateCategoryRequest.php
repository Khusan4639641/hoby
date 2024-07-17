<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateCategoryRequest extends FormRequest
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
            'parent_id' => 'integer',
            'psic_code' => 'nullable|string|min:1|max:255',
            'psic_text' => 'nullable|string|min:1|max:255',
            'status' => 'integer',
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
