<?php

namespace App\Rules;

use App\Models\Card;
use Illuminate\Contracts\Validation\Rule;

class CheckIfCardIsActive implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
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
        if(Card::where('id', $value)->pluck('status')->first() === 1) return true;

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('card.card_is_not_active_in_our_system');
    }
}
