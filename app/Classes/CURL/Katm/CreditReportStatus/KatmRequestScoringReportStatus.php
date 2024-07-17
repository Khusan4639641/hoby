<?php

namespace App\Classes\CURL\Katm\CreditReportStatus;

use App\Classes\ApiResponses\Katm\Reports\KatmResponseReport;
use App\Classes\ApiResponses\Katm\Reports\KatmResponseScoringReport;
use App\Classes\CURL\Katm\KatmRequestCreditReportStatus;

class KatmRequestScoringReportStatus extends KatmRequestCreditReportStatus
{

    public function report(): KatmResponseReport
    {
        $response = $this->response()->json();
        $decodedReport = base64_decode($response['data']['reportBase64']);
        return new KatmResponseScoringReport($decodedReport);
    }
}
