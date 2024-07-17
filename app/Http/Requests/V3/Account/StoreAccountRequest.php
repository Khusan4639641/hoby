<?php

namespace App\Http\Requests\V3\Account;

use App\DTO\V3\Account\Account1cDTO;
use App\DTO\V3\Account\MFOAccountDTO;
use App\Services\API\V3\BaseService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
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
            'mfo_account_number' => ['required', 'string', 'min:20', 'max:20'],
            'account_1c_number' => ['required', 'string', 'max:6'],
            'account_1c_name' => ['required', 'string', 'max:191'],
            'is_subconto' => ['required', 'boolean'],
            'subconto_number' => ['required_if:is_subconto,1', 'nullable', 'string', 'max:20'],
            'account_type' => ['digits_between:1,9', 'numeric'],
            'account_system_number' => ['max:10'],
            'is_subconto_without_remainder' => ['boolean'],
        ];
    }

    public function passedValidation()
    {
        $this['mfo_account_dto'] = new MFOAccountDTO(
            $this->mfo_account_number,
        );
        $this['account_1c_dto'] = new Account1cDTO(
            $this->account_1c_number,
            $this->account_1c_name,
            $this->is_subconto,
            $this->subconto_number,
            $this->account_type,
            $this->account_system_number,
            (bool)$this->is_subconto_without_remainder,
        );
    }

    public function FailedValidation(Validator $validator)
    {
        BaseService::handleError($validator->errors()->all());
    }
}
