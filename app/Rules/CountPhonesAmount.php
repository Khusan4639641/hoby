<?php

namespace App\Rules;

use App\Enums\CategoriesEnum;
use App\Models\Contract;
use App\Services\API\V3\Partners\BuyerService;
use Illuminate\Contracts\Validation\Rule;

class CountPhonesAmount implements Rule
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
        $contract = Contract::find($value);
        if (!$contract) {
            return false;
        }
        if (!$contract->user_id) {
            return false;
        }
        $contractPhonesCount = BuyerService::getPhonesCountByCategories($contract->orderProducts->pluck('category_id')->all());
        if ($contractPhonesCount === 0) {
            return true;
        }
        return $contractPhonesCount + BuyerService::getPhonesCount($contract->user_id)
            <= CategoriesEnum::ALLOWED_AMOUNT_OF_PHONES;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('api.you_have_exceeded_the_maximum_allowable_number_of_contracts_in_the_phones_category');
    }
}
