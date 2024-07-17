<?php

namespace App\Classes\ApiResponses\Interfaces;

use App\Classes\ApiResponses\Katm\Reports\KatmResponseReport;

interface IKatmReport
{

    public function report(): KatmResponseReport;

}
