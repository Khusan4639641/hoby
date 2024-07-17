<?php

namespace App\Http\Requests;

use App\Rules\CheckByPhonePrefixCode;
use App\Rules\CheckGuarantPhone;
use App\Rules\CompareBuyersPhoneToGuarants;
use App\Rules\CompareIfArrayElementsAreUnique;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddGuarantRequest extends FormRequest
{
    public function rules()
    {
        return [
            'buyer_id' => [ 'required', 'exists:users,id' ],
            'name'     => [ 'required' ],
            'name.*'   => [ 'required' ],
            'phone'    => [ 'required', new CompareBuyersPhoneToGuarants($this->buyer_id), new CompareIfArrayElementsAreUnique() , new CheckGuarantPhone(),  new CheckByPhonePrefixCode],
            'phone.*'  => [ 'required', new CompareBuyersPhoneToGuarants($this->buyer_id), new CheckGuarantPhone(),  new CheckByPhonePrefixCode],
        ];
    }

    public function messages()
    {
        return [
            'buyer_id.exists'   => __('api.buyer_does_not_exist'),
            'name.required'     => __('api.buyer_name_is_required'),
            'buyer_id.required' => 'buyer_id_is_required',
        ];
    }

    public function FailedValidation(Validator $validator)
    {
        $validationErrors = $validator->errors()->messages();
        $result['status']             = 'error';
        $result['response']['code'] = '';
        $result['response']['message'][] = [
            'type' => 'danger',
            'text' => reset($validationErrors)[0]
        ];
        $result['response']['errors'] = reset($validationErrors);
        $result['data'] = [];
        if($validator->fails()) {
            return response()->json($result, 402);
        }
    }
}
