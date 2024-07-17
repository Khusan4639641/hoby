<?php

namespace App\Classes\CURL\Katm\CreditReport;

use App\Classes\ApiResponses\Interfaces\IKatmReport;
use App\Classes\ApiResponses\Katm\Reports\KatmResponseMibReport;
use App\Classes\ApiResponses\Katm\Reports\KatmResponseReport;
use App\Classes\CURL\Katm\KatmRequestCreditReport;

class KatmRequestMibReport extends KatmRequestCreditReport implements IKatmReport
{

    protected function reportType(): int
    {
        return self::TYPE_REPORT_MIB;
    }

    public function report(): KatmResponseReport
    {
        $response = $this->response()->json();
        $decodedReport = base64_decode($response['data']['reportBase64']);
        return new KatmResponseMibReport($decodedReport);
    }

}
