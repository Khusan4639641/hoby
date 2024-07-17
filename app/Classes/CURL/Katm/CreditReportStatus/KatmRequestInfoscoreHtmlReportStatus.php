<?php

namespace App\Classes\CURL\Katm\CreditReportStatus;

use App\Classes\ApiResponses\Katm\Reports\KatmResponseReport;
use App\Classes\CURL\Katm\KatmRequestCreditReportStatus;

class KatmRequestInfoscoreHtmlReportStatus extends KatmRequestCreditReportStatus
{

    protected function reportFormat(): int
    {
        return self::REPORT_FORMAT_XML;
    }

    public function report(): KatmResponseReport
    {
        $response = $this->response()->json();
        $decodedReport = base64_decode($response['data']['reportBase64']);
        return new KatmResponseReport($decodedReport);
    }

    public function reportHtml(): string
    {
        $response = $this->response()->json();
        return base64_decode($response['data']['reportBase64']);
    }

}
