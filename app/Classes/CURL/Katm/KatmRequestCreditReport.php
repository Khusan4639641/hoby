<?php

namespace App\Classes\CURL\Katm;

use App\Classes\ApiResponses\Katm\KatmResponseCreditReport;

abstract class KatmRequestCreditReport extends KatmRequest
{

    const LEGAL_ENTITY = 0;
    const INDIVIDUAL = 1;

    const REPORT_FORMAT_XML = 0;
    const REPORT_FORMAT_JSON = 1;

    const TYPE_REPORT_MIB = 39;
    const TYPE_REPORT_SCORING = 23;
    const TYPE_REPORT_INFOSCORE = 77;
    const TYPE_REPORT_INFOSCORE_HTML = 177;

    public function __construct(string $claimID)
    {
        parent::__construct($claimID);
        $head = config('test.katm_api_head');
        $this->addParam('pReportId', $this->reportType());
        $this->addParam('pHead', $head);
        $this->addParam('pLegal', self::INDIVIDUAL);
        $this->addParam('pReportFormat', $this->reportFormat());
        $this->addParam('pYear', 0);
        $this->addParam('pQuarter', 0);
    }

    protected function reportFormat(): int
    {
        return self::REPORT_FORMAT_JSON;
    }

    abstract protected function reportType(): int;

    public function url(): string
    {
        return $this->baseUrl . 'credit/report';
    }

    public function response(): KatmResponseCreditReport
    {
        return new KatmResponseCreditReport($this->responseBody->json());
    }

}
