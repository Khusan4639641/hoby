<?php

namespace App\Classes\Universal\Autopayment;


class Switcher extends BaseAutopayment
{

    public function __construct(bool $switch)
    {
        parent::__construct();
        $this->makeRequest('toggle.auto');
        $this->addParam('status', $switch);
    }

}
