<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use App\Rules\CheckSellerTotalBonusPercent;

class UpdateSellerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:15', 'regex:/^\+?[0-9]+$/'],
            'pinfl' => ['required', 'string'],
            /* Skip to phase 2 */
            /*'seller_bonus_percent' => ['required', 'integer', 'between:0,100', new CheckSellerTotalBonusPercent()],
            'bonus_sharers' => ['array'],
            'bonus_sharers.*.sharer_id' => ['required', 'integer'],
            'bonus_sharers.*.percent' => ['required', 'integer', 'between:0,100'],*/
        ];
    }
}
