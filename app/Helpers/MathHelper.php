<?php


namespace App\Helpers;


use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class MathHelper
{

    public static function calcNDS($price){

        return $price / NdsStopgagHelper::getActualNdsPlusOne() * NdsStopgagHelper::getActualNds();

    }

}
