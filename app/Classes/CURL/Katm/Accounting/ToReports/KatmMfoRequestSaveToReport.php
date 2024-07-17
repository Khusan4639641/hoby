<?php

namespace App\Classes\CURL\Katm\Accounting\ToReports;

use App\Classes\CURL\Katm\Interfaces\ReportDataInterface;
use App\Classes\CURL\Katm\MFO\KatmMfoRequestReport;

class KatmMfoRequestSaveToReport extends KatmMfoRequestReport implements ReportDataInterface
{

//    START

    public function __construct(array $data)
    {
        parent::__construct(0);
        $this->requestBody = $data;
    }

}
