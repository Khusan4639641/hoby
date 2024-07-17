<?php

namespace App\Classes\CURL\Katm\Accounting\ToReports;

use App\Classes\CURL\Katm\Accounting\KatmAccountingRefuse;
use App\Classes\CURL\Katm\Interfaces\ReportDataInterface;

class KatmAccountingRefuseToReport extends KatmAccountingRefuse implements ReportDataInterface
{

//    003

    public function __construct(array $data)
    {
        parent::__construct(0, now(), 0, "", "");
        $this->requestBody = $data;
    }

}
