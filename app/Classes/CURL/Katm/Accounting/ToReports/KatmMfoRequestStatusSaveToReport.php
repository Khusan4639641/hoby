<?php

namespace App\Classes\CURL\Katm\Accounting\ToReports;

use App\Classes\CURL\Katm\Interfaces\ReportDataInterface;
use App\Classes\CURL\Katm\MFO\KatmMfoRequestReportStatus;

class KatmMfoRequestStatusSaveToReport extends KatmMfoRequestReportStatus implements ReportDataInterface
{

//    START STATUS

    public function __construct(array $data)
    {
        parent::__construct(0, "");
        $this->requestBody = $data;
    }

}
