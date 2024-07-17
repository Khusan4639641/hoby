<?php

namespace App\Rules;

use App\Models\Buyer;
use Illuminate\Contracts\Validation\Rule;

class CompareBuyersPhoneToGuarants implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($buyer_id)
    {
        $this->buyer_id = $buyer_id;
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
        $value = preg_replace('/[^0-9]/', '', $value);

        if($buyer = Buyer::find($this->buyer_id))
        {
            if(is_array($value))
            {
                foreach($value as $phone) if($buyer->phone == $phone) return false;
            }
            else
            {
                if($buyer->phone == $value) return false;
            }
        }
        else
        {
            return false;
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
        return __('api.guarants_phone_should_not_be_equal_to_buyers');
    }
}
