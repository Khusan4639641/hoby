<?php

namespace App\Classes\CURL\Katm\CreditReportStatus;

use App\Classes\ApiResponses\Katm\Reports\KatmResponseInfoscoreReport;
use App\Classes\ApiResponses\Katm\Reports\KatmResponseReport;
use App\Classes\CURL\Katm\KatmRequestCreditReportStatus;

class KatmRequestInfoscoreReportStatus extends KatmRequestCreditReportStatus
{

    public function report(): KatmResponseReport
    {
        $response = $this->response()->json();
        $decodedReport = base64_decode($response['data']['reportBase64']);
        return new KatmResponseInfoscoreReport($decodedReport);
    }
}
