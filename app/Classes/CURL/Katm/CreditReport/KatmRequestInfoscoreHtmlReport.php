<?php

namespace App\Classes\CURL\Katm\CreditReport;

use App\Classes\CURL\Katm\KatmRequestCreditReport;

class KatmRequestInfoscoreHtmlReport extends KatmRequestCreditReport
{

    protected function reportFormat(): int
    {
        return self::REPORT_FORMAT_XML;
    }

    protected function reportType(): int
    {
        return self::TYPE_REPORT_INFOSCORE_HTML;
    }

}
