<?php

namespace App\Classes\CURL\Katm\Accounting\ToReports;

use App\Classes\CURL\Katm\Accounting\KatmAccountingPayments;
use App\Classes\CURL\Katm\Interfaces\ReportDataInterface;

class KatmAccountingPaymentsToReport extends KatmAccountingPayments implements ReportDataInterface
{

//    016

    public function __construct(array $data)
    {
        parent::__construct(0, "");
        $this->requestBody = $data;
    }

}
