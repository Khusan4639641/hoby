<?php

namespace App\Classes\CURL\Katm;

use App\Classes\ApiResponses\Interfaces\IKatmReport;
use App\Classes\ApiResponses\Katm\Reports\KatmResponseReport;

abstract class KatmRequestCreditReportStatus extends KatmRequest implements IKatmReport
{

    const REPORT_FORMAT_XML = 0;
    const REPORT_FORMAT_JSON = 1;

    public function __construct(string $claimID, string $token)
    {
        parent::__construct($claimID);
        $head = config('test.katm_api_head');
        $this->addParam('pToken', $token);
        $this->addParam('pHead', $head);
        $this->addParam('pReportFormat', $this->reportFormat());
    }

    protected function reportFormat(): int
    {
        return self::REPORT_FORMAT_JSON;
    }

    public function url(): string
    {
        return $this->baseUrl . 'credit/report/status';
    }

    abstract public function report(): KatmResponseReport;

}
