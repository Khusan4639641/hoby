<?php

namespace App\Rules;

use App\Enums\CategoriesEnum;
use App\Models\Contract;
use App\Services\API\V3\Partners\BuyerService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Contracts\Validation\Rule;

class CheckByPhonePrefixCode implements Rule
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
        $prefix = preg_replace(['/^[0-9]{3}/i','/[0-9]{7}$/i'], '', $value);
        if (!in_array($prefix,config('test.phone_prefix'))){
            $this->errorMessage = __('api.error_phone_prefix');
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
