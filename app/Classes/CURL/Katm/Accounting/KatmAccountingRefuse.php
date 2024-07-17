<?php

namespace App\Classes\CURL\Katm\Accounting;

class KatmAccountingRefuse extends KatmAccounting
{

//    003

    public function __construct(
        $claimID,
        $date,
        $number,
        $reason,
        $reasonDescription
    )
    {
        parent::__construct();

        $this->addParam('pClaimId', (string)$claimID);

        $this->addParam('pDeclineDate', $this->convertDate($date));
        $this->addParam('pDeclineNumber', (string)$number);
        $this->addParam('pDeclineReason', (string)$reason);
        $this->addParam('pDeclineReasonNote', (string)$reasonDescription);
    }

    public function url(): string
    {
        return $this->baseUrl . 'katm-api/v1/claim/decline';
    }

}
