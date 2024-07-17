<?php

namespace App\Rules;

use App\Models\DebtCollect\DebtCollector;
use Illuminate\Contracts\Validation\Rule;

class CheckAllowedDebtorsToCollector implements Rule
{

    private $debtCollector;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(DebtCollector $debtCollector)
    {
        $this->debtCollector = $debtCollector;
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
        if (!$this->debtCollector) {
            return false;
        }
        return $this->debtCollector->debtors()->withOverdueContracts(1)->count() + count($value) <= 150;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return __('collector.achieved_debtors_limit');
    }
}
