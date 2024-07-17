<?php

namespace App\Classes\CURL\Katm\Accounting;

class KatmAccountingRepaymentSchedule extends KatmAccounting
{

//    005

    private array $schedule = [];

    public function __construct(
        $claimID,
        $contractID,
        $nibbd // user.nibbd
    )
    {
        parent::__construct();

        $this->addParam('pClaimId', (string)$claimID);
        $this->addParam('pContractId', (string)$contractID);
        $this->addParam('pNibbd', (string)$nibbd);
    }

    public function addSchedule(
        $date,
        $percent, // в тийинах
        $currency,
        $amount
    ): void
    {
        $this->schedule[] = [
            // @todo поменять формат даты везде
            'date' => $this->convertDate($date),
            'percent' => $this->setAmountFormat($percent),
            'currency' => (string)$currency,
            'amount' => $this->setAmountFormat($amount),
        ];
        $this->addParam('pPlanArray', $this->schedule);
    }

    public function url(): string
    {
        return $this->baseUrl . 'katm-api/v1/credit/registration/repayment/schedule';
    }

}
