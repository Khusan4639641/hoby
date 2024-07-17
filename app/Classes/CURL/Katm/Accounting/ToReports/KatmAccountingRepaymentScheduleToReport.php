<?php

namespace App\Classes\CURL\Katm\Accounting\ToReports;

use App\Classes\CURL\Katm\Accounting\KatmAccountingRepaymentSchedule;
use App\Classes\CURL\Katm\Interfaces\ReportDataInterface;

class KatmAccountingRepaymentScheduleToReport extends KatmAccountingRepaymentSchedule implements ReportDataInterface
{

//    005

    public function __construct(array $data)
    {
        parent::__construct(0, 0, "");
        $this->requestBody = $data;
    }

}
