<?php

namespace App\Rules;

use App\Classes\CURL\test\CardRequest;
use App\Models\Buyer;
use App\Models\BuyerGuarant;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckGuarantPhone implements Rule
{

    private string $errorMessage = '';

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

        if(BuyerGuarant::where('phone', $value)->count()>=5){
            $this->errorMessage = __('api.error_phone_exist');
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
        return $this->errorMessage;
    }
}
