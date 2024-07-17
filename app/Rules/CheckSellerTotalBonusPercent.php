<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CheckSellerTotalBonusPercent implements Rule
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
        $total_percent = $value;

        if (request()->bonus_sharers) {
          foreach (request()->bonus_sharers as $bonus_sharer) {
            $total_percent += $bonus_sharer['percent'];
          }
        }

        return $total_percent == 100;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The validation error message.';
    }
}
