<?php

namespace App\Http\Requests\V3\Prepay;

use App\Rules\CheckIfApiTokenBelongsToUserId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use App\Services\API\V3\BaseService;


class PrepayFreePayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array {
        return [
            'type'        => [ 'required', Rule::in(["CARD", "ACCOUNT"]) ],
            'user_id'     => [ 'nullable', 'integer', new CheckIfApiTokenBelongsToUserId() ],
            'contract_id' => [ 'required', 'integer', 'exists:contracts,id' ],
            'card_id'     => [ 'required_if:type,==,card', 'integer', 'exists:cards,id' ],
            'otp'         => [ 'nullable', 'integer' ],
            'amount'      => [ 'required', 'integer' ],
        ];
    }

    protected function failedValidation(Validator $validator): void {
        BaseService::handleError($validator->errors()->getMessages());
    }
}
