<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Core\CoreController;

class MobileController extends CoreController{

    public function association(){

        return ["applinks"=>[
               "apps" => [],

            ]
        ];

    }
}
