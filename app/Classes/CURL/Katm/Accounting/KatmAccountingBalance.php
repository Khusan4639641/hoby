<?php

namespace App\Classes\CURL\Katm\Accounting;

class KatmAccountingBalance extends KatmAccounting
{

//    015

    private array $repayments = [];

    public function __construct(
        $contractID
    )
    {
        parent::__construct();

        $this->addParam('pContractId', (string)$contractID);

        $this->addParam('pDate', $this->convertDate(date('Y-m-d H:i:s')));
        $this->addParam('pLoanStatus', "1");
    }

    public function addRepayment(
        $account,
        $date,
        $startBalance,
        $debit,
        $credit,
        $endBalance
    ): void
    {
        $this->repayments[] = [
            'account' => (string)$account,
            'date' => $this->convertDate($date),
            'startBalance' => $this->setAmountFormat($startBalance),
            'debit' => $this->setAmountFormat($debit),
            'credit' => $this->setAmountFormat($credit),
            'endBalance' => $this->setAmountFormat($endBalance),
        ];
        $this->addParam('pRepaymentArray', $this->repayments);
    }

    public function url(): string
    {
        return $this->baseUrl . 'katm-api/v1/credit/registration/repayment';
    }

}
