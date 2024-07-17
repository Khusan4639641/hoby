<?php

namespace App\Rules;

use DateTime;
use Illuminate\Contracts\Validation\Rule;

class CheckCardValidDateV2 implements Rule
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
        $expiry = str_replace('/', '', $value);

        $expiryMonth = mb_substr($expiry, 0, 2);
        $expiryYear = mb_substr($expiry, 2, 2);

        $now = new DateTime();
        $expiresAt = DateTime::createFromFormat('my', $expiryMonth.$expiryYear);

        return $expiryMonth > 0 && $expiryMonth < 13 && $expiresAt > $now;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('test_card_service/card.expiry_is_not_correct');
    }
}
