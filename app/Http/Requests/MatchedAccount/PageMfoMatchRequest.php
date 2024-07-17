<?php

namespace App\Http\Requests\MatchedAccount;

use App\Rules\CheckByPhonePrefixCode;
use App\Rules\CheckGuarantPhone;
use App\Rules\CompareBuyersPhoneToGuarants;
use App\Rules\CompareIfArrayElementsAreUnique;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PageMfoMatchRequest extends FormRequest
{
    public function rules()
    {
        return [
            'page'  => 'sometimes|required|integer|min:1',
            'limit' => 'sometimes|required|integer|max:100'
        ];
    }
}
