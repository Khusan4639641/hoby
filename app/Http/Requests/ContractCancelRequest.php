<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class ContractCancelRequest extends FormRequest
{
    public function rules(): array
    {
        return ['contract_id' => 'required|integer|exists:contracts,id',
                'password'    => ['required',
                                  function ($attribute, $value, $fail) {
                                      if (!Hash::check($this->password, config('test.cancel_contract_password'))) {
                                          $fail('Введен неверный пароль');
                                      }
                                  }],
        ];
    }
}
