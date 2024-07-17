<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CheckCardValidDate implements Rule
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
        if(str_contains($value,'/'))
            list($exp_m, $exp_y) = explode('/', $value);
        else
        {
            $exp_m = mb_substr($value, 0, 2);
            $exp_y = mb_substr($value, 2, 2);
        }
        $expires = \DateTime::createFromFormat('my', $exp_m.$exp_y);
        $now     = new \DateTime();

        if($exp_m > 0 && $exp_m < 13 && $expires > $now)
            return true;

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('billing/buyer.card_date_is_not_valid');
    }
}
