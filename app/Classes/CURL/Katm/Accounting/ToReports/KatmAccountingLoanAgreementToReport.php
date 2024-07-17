<?php

namespace App\Classes\CURL\Katm\Accounting\ToReports;

use App\Classes\CURL\Katm\Accounting\KatmAccountingLoanAgreement;
use App\Classes\CURL\Katm\Interfaces\ReportDataInterface;

class KatmAccountingLoanAgreementToReport extends KatmAccountingLoanAgreement implements ReportDataInterface
{

//    004

    public function __construct(array $data)
    {
        parent::__construct(0, 0, "", "", "", "", now(), now(), 0, "");
        $this->requestBody = $data;
    }

}
