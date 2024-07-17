<?php

namespace App\Classes\CURL\Katm;

abstract class KatmRequest extends BaseKatmRequest
{

    const CURRENCY = 860;

    private string $claimID;

    public function __construct(string $claimID)
    {
        $this->claimID = $claimID;
        parent::__construct();
        $this->addParam('pClaimId', $claimID);
        $this->addParam('pCode', config('test.katm_api_code'));
    }

    protected function addParam($key, $value)
    {
        $this->requestBody['data'][$key] = $value;
    }

    public function getClaimID(): string
    {
        return $this->claimID;
    }

    static public function generateClaimID(string $alias): string
    {
        return mb_substr($alias . md5('pm-' . time()), 0, 20);
    }

}
