<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class CompareIfArrayElementsAreUnique implements Rule
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
        if(is_array($value) && count($value) > 1)
        {
            for($i = 0; $i < count($value); $i++)
            {
                for($j = $i + 1; $j < count($value); $j++)
                {
                    if($value[$i] == $value[$j]) return false;
                }
            }
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('api.buyer_guarants_phones_are_equal');
    }
}
