<?php

namespace App\Classes\CURL\Katm\CreditReport;

use App\Classes\CURL\Katm\KatmRequestCreditReport;

class KatmRequestScoringReport extends KatmRequestCreditReport
{

    protected function reportType(): int
    {
        return self::TYPE_REPORT_SCORING;
    }

}
