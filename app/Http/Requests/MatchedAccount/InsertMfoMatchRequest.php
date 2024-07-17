<?php

namespace App\Http\Requests\MatchedAccount;

use App\Rules\CheckByPhonePrefixCode;
use App\Rules\CheckGuarantPhone;
use App\Rules\CompareBuyersPhoneToGuarants;
use App\Rules\CompareIfArrayElementsAreUnique;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class InsertMfoMatchRequest extends FormRequest
{
    public function rules()
    {
        return [
            'mfo_mask'         => 'sometimes|required|min:1|max:5',
            'one_c_mask'          => 'sometimes|required|min:1|max:6',
            'parent_id'        => 'sometimes|integer|nullable|min:1',
            'number'           => 'sometimes|required|min:1',
            'mfo_account_name' => 'sometimes|required|min:1'
        ];
    }
}
