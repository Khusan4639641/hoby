<?php

namespace App\Classes\CURL\Katm\MFO;

use App\Classes\CURL\Katm\KatmRequestCreditReport;

class KatmMfoRequestReport extends KatmRequestCreditReport
{

    public function __construct(string $claimID)
    {
        parent::__construct($claimID);

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

    protected function reportType(): int
    {
        return self::TYPE_REPORT_SCORING;
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
