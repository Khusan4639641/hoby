<?php

namespace App\Http\Requests;

use App\Rules\CheckIfApiTokenBelongsToUserId;
use App\Rules\CheckIfCardIsActive;
use App\Services\API\V3\BaseService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ContractPayV2Request extends FormRequest
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
            'user_id'      => ['required', new CheckIfApiTokenBelongsToUserId(),'integer'],
            'contract_id'  => ['required','exists:contracts,id'],
            'type'         => ['required','string'], // card or account
            'payment_type' => ['required','string'], // month/several-month/free-pay
            'card_id'      => ['required_if:type,==,card', 'exists:cards,id', new CheckIfCardIsActive()],
            'amount'       => ['required_if:payment_type,==,free-pay','integer','min:1'],
            'schedule_ids' => ['required_if:payment_type,==,several-month'],
        ];
    }

    public function messages()
    {
        return
        [
            'card_id.exists' => __('card.card_does_not_exist'),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        BaseService::handleError($validator->errors()->getMessages());

    }
}
