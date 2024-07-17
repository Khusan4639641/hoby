<?php

namespace App\Classes\CURL\Katm\MFO;

use App\Classes\ApiResponses\Katm\Reports\KatmResponseReport;
use App\Classes\ApiResponses\Katm\Reports\KatmResponseScoringReport;
use App\Classes\CURL\Katm\KatmRequestCreditReportStatus;

class KatmMfoRequestReportStatus extends KatmRequestCreditReportStatus
{

    public function __construct(string $claimID, string $token)
    {
        parent::__construct($claimID, $token);

        $this->baseUrl = config('test.katm_shaffof_moliya.base_url');

        $this->addParam('pCode', config('test.katm_shaffof_moliya.code'));
        $this->addParam('pHead', config('test.katm_shaffof_moliya.head'));
    }

    protected function getLogin(): string
    {
        return config('test.katm_shaffof_moliya.login');
    }

    protected function getPassword(): string
    {
        return config('test.katm_shaffof_moliya.password');
    }

    public function report(): KatmResponseReport
    {
        $response = $this->response()->json();
        $decodedReport = base64_decode($response['data']['reportBase64']);
        return new KatmResponseScoringReport($decodedReport);
    }

    public function getRequestData(): array
    {
        return $this->requestBody;
    }

    public function getRequestText(): string
    {
        return json_encode($this->requestBody);
    }

}
