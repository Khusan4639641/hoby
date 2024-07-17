<?php

namespace App\Classes\CURL\Katm\Accounting\ToReports;

use App\Classes\CURL\Katm\Accounting\KatmAccountingStatus;
use App\Classes\CURL\Katm\Interfaces\ReportDataInterface;

class KatmAccountingStatusToReport extends KatmAccountingStatus implements ReportDataInterface
{

//    018

    public function __construct(array $data)
    {
        parent::__construct(0, "");
        $this->requestBody = $data;
    }

}
