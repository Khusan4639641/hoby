<?php

namespace App\Rules;

use App\Models\CatalogPartners;
use Illuminate\Contracts\Validation\Rule;

class CheckIfVendorHasThisCategory implements Rule
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
        foreach($value as $key => $val) {
            foreach($val as $v) {
                $object = (object)$v;
                $sendedCategories[] = (int)$object->category;
            }
        }

        $categories = CatalogPartners::where('partner_id', array_key_first($value))->pluck('catalog_id');
        $categories = json_decode($categories);

        // Проверяем принадлежат ли этому вендору категории, которые от ввел
        foreach($sendedCategories as $category)
            if(!in_array($category, $categories))
                return false;

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The validation error message.';
    }
}
