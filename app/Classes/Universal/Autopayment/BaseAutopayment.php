<?php

namespace App\Classes\Universal\Autopayment;

use App\Classes\Universal\BaseUniversal;

class BaseAutopayment extends BaseUniversal
{

    const ENV_URL = 'UNIVERSAL_AUTOPAYMENT_URL';
    const ENV_TOKEN = 'UNIVERSAL_AUTOPAYMENT_TOKEN';

    public function __construct()
    {
        parent::__construct();
    }

}
