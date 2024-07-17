<?php

namespace App\Classes\CURL\Katm\Accounting\ToReports;

use App\Classes\CURL\Katm\Accounting\KatmLoanRegistration;
use App\Classes\CURL\Katm\Interfaces\ReportDataInterface;

class KatmLoanRegistrationToReport extends KatmLoanRegistration implements ReportDataInterface
{

//    001

    public function __construct(array $data)
    {
        parent::__construct(0, "", "", now(), "", "", "", "", now(), "", "", now(), "", "", "", "", 0, 0, "", "", "");
        $this->requestBody = $data;
    }

}
