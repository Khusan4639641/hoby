<?php

namespace App\Http\Requests\Core\CompatibleApiController;

use App\Models\Buyer;
use App\Models\Contract;
use App\Rules\CountPhonesAmount;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CheckContractSmsCodeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->buyer_id = Buyer::where('phone', $this->phone)->pluck('id')->first();
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
            'phone'       => [ 'exists:users,phone' ],
            'contract_id' => [
                'bail',
                'required',
                new CountPhonesAmount(),
                Rule::exists('contracts', 'id')->where('user_id', $this->buyer_id)
                    ->whereIn('status', [ Contract::STATUS_AWAIT_SMS, Contract::STATUS_AWAIT_VENDOR, Contract::STATUS_MODERATION_1, Contract::STATUS_MODERATION_2, Contract::STATUS_MODERATION_3, Contract::STATUS_MODERATION_4 ])
            ],
        ];
    }

    public function messages()
    {
        return [
            'phone.exists'       => 'buyer_not_found',
            'contract_id.exists' => 'contract_not_found_or_status_is_incorrect',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        $validationErrors = $validator->errors()->messages();
        Log::channel('contracts')->info('1. Валидация не пройдена. CompatibleApiController ---> CheckContractSmsCodeRequest');
        Log::channel('contracts')->info('2. Полученные данные ' . json_encode($this->all()));
        Log::channel('contracts')->info($this->buyer_id ? '3. ID покупателя: ' . $this->buyer_id : '3. Поиск ID по номеру телефона не дал результатов');
        Log::channel('contracts')->info('4. Ошибки валидации ' . json_encode($validationErrors));

        $result['status'] = 'error';
        $result['response']['code'] = "";
        $result['response']['message'] = reset($validationErrors)[0];
        $result['response']['errors'] = [];
        $result['data'] = [];

        throw new HttpResponseException(response()->json($result));
    }
}
