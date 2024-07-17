<?php

namespace App\Http\Requests;

use App\Models\Card;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * В реквесте возвращаются объекты пользователя и карты, чтобы исключить лишние запросы в БД
 * $request->user
 * $request->card
 */
class AddDepositRequest extends FormRequest
{
    public function rules(): array
    {
        return ['card_id' => ['required',
                              'numeric',
                              function ($attribute, $value, $fail) {
                                  $this->offsetSet('user', auth()->user());
                                  $card = Card::where('user_id', $this->user->id)->find($value);
                                  if ($card === null) {
                                      $fail(__('card.card_not_found'));
                                  }
                                  $this->offsetSet('card', $card);
                              }],
                'sum'     => 'required|numeric|gt:1',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        $result['status']                = 'error';
        $result['response']['code']      = 422;
        $result['response']['message'][] = [
            'type' => 'danger',
            'text' => $validator->errors()->first(),
        ];
        $result['response']['errors']    = [];
        $result['data']                  = [];
        throw new HttpResponseException(response()->json($result));
    }
}
