<?php

namespace App\Rules;

use App\Helpers\BuyerBlockingChecker;
use Illuminate\Contracts\Validation\Rule;

class CheckInBannedList implements Rule
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
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $serial = substr($value, 0, 2);
        $number = substr($value, 2, 8);
        return !BuyerBlockingChecker::isUserBanned($serial, $number);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('Отказано в регистрации. Покупатель в списке заблокированных');
    }
}
