<?php

namespace App\Rules;

use App\Models\KatmRegion;
use Illuminate\Contracts\Validation\Rule;

class RegionRelevant implements Rule
{

    private string $regionAlias;
    private string $localRegionAlias;
    private array $data;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(array $data, string $regionAlias = 'region_id', string $localRegionAlias = 'local_region_id')
    {
        $this->regionAlias = $regionAlias;
        $this->localRegionAlias = $localRegionAlias;
        $this->data = $data;
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
        if (!isset($this->data[$this->regionAlias])) {
            return false;
        }
        if (!isset($this->data[$this->localRegionAlias])) {
            return false;
        }

        $region = $this->data[$this->regionAlias];
        $localRegion = $this->data[$this->localRegionAlias];

        $count = KatmRegion::query()
            ->where('region', $region)
            ->where('local_region', $localRegion)
            ->count();

        if ($count == 0) {
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
        return __('Район не относиться к региону');
    }
}
