<?php

namespace App\Rules;

use App\Helpers\BuyerBlockingChecker;
use Illuminate\Contracts\Validation\Rule;

class CheckInBlackList implements Rule
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
        return !BuyerBlockingChecker::isUserInBlackList($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('Отказано в регистрации. Покупатель в чёрном списке');
    }
}
