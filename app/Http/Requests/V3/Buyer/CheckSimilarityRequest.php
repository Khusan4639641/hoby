<?php

namespace App\Http\Requests\V3\Buyer;

use App\Services\API\V3\BaseService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class CheckSimilarityRequest extends FormRequest
{

    public function rules(): array
    {
        return ['user_id'     => 'required|int|exists:users,id',
                'min_percent' => 'required|int|min:1|max:100'];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        BaseService::handleError($validator->errors()->getMessages());
    }
}
