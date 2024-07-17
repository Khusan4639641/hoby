<?php

namespace App\Rules;

use App\Models\Card;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class HashedCardNumberCheckV2 implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $pan = str_replace(' ', '', $value);

        $cardExists = Card::where([
            ['guid', '=', md5($pan)],
            ['user_id', '=', request('buyer_id') ?? Auth::user()->id]
        ])->exists();

        return !$cardExists;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('test_card_service/card.card_already_registered_in_system');
    }
}
