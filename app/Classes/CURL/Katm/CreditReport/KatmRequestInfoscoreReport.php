<?php

namespace App\Classes\CURL\Katm\CreditReport;

use App\Classes\CURL\Katm\KatmRequestCreditReport;

class KatmRequestInfoscoreReport extends KatmRequestCreditReport
{

    protected function reportType(): int
    {
        return self::TYPE_REPORT_INFOSCORE;
    }

}
