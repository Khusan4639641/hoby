<?php

namespace App\Http\Requests\Core\CompatibleApiController;

use App\Models\Buyer;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SendContractSmsCodeRequest extends FormRequest
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
            'phone'       => [ Rule::exists('users', 'phone') ],
            'contract_id' => [ 'required', 'exists:contracts,id,user_id,' . $this->buyer_id ],
        ];
    }

    public function messages()
    {
        return [
            'phone.exists'       => 'buyer_not_found',
            'contract_id.exists' => 'contract_not_found',
        ];
    }

    public function failedValidation(Validator $validator)
    {
        $validationErrors = $validator->errors()->messages();
        Log::channel('contracts')->info('1. Валидация не пройдена. CompatibleApiController ---> SendContractSmsCodeRequest');
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
