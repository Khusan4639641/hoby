<?php

namespace App\Classes\CURL\Katm\Accounting\ToReports;

use App\Classes\CURL\Katm\Accounting\KatmAccountingBalance;
use App\Classes\CURL\Katm\Interfaces\ReportDataInterface;

class KatmAccountingBalanceToReport extends KatmAccountingBalance implements ReportDataInterface
{

//    015

    public function __construct(array $data)
    {
        parent::__construct(0);
        $this->requestBody = $data;
    }

}

