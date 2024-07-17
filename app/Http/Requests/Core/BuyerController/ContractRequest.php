<?php

namespace App\Http\Requests\Core\BuyerController;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class ContractRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $this->buyer_id = Auth::user()->id;
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
            'contract_id' => ['required', 'exists:contracts,id,user_id,' . $this->buyer_id]
        ];
    }

    public function messages()
    {
        return [
            'contract_id.exists' => trans('app.contract_not_found', ['contract_id' => $this->contract_id]),
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $validationErrors = $validator->errors()->messages();

        // response подогнан под фронт
        $result['status'] = 'error';
        $result['response']['code'] = 400;
        $result['response']['message'][] = [
            'type' => 'danger',
            'text' => reset($validationErrors)[0],
        ];
        $result['response']['errors'] = [];
        $result['data'] = [];

        throw new HttpResponseException(response()->json($result));
    }
}
