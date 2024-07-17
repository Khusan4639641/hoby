<?php

namespace App\Classes\CURL\Katm\Accounting;

class KatmAccountingStatus extends KatmAccounting
{

//    018

    private array $statuses = [];

    public function __construct(
        $contractID,
        $contractType
    )
    {
        parent::__construct();

        $this->addParam('pContractId', (string)$contractID);
        $this->addParam('pContractType', (string)$contractType);
        $this->addParam('pDate', $this->convertDate(date('Y-m-d H:i:s')));
    }

    public function addAccountStatus(
        $date,
        $account,
        $coa,
        $dateOpen,
        $dateClose
    )
    {
        $this->statuses[] = [
            'date' => $this->convertDate($date),
            'account' => (string)$account,
            'coa' => (string)$coa,
            'dateOpen' => $this->convertDate($dateOpen),
            'dateClose' => $dateClose ? $this->convertDate($dateClose) : "",
        ];
        $this->addParam('pAccountStatusesArray', $this->statuses);
    }

    public function url(): string
    {
        return $this->baseUrl . 'katm-api/v1/credit/account/status';
    }

}
