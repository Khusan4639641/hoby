<?php

namespace App\Facades\KATM;

use Illuminate\Support\Facades\Facade;

class SaveKatm extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'saveKatm';
    }
}
