<?php

namespace App\Classes\CURL\Katm\Accounting;

class KatmAccountingPayments extends KatmAccounting
{

//    016

    private array $repayments = [];

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

    public function addPayment(
        $accountA,
        $accountB,
        $branchA,
        $branchB,
        $coaA,
        $coaB,
        $currency,
        $destination,
        $docDate,
        $docNum,
        $docType,
        $nameA,
        $nameB,
        $payType,
        $paymentId,
        $purpose,
        $summa
    ): void
    {
        $this->repayments[] = [
            'accountA' => (string)$accountA,
            'accountB' => (string)$accountB,
            'branchA' => (string)$branchA,
            'branchB' => (string)$branchB,
            'coaA' => (string)$coaA,
            'coaB' => (string)$coaB,
            'currency' => (string)$currency,
            'destination' => (string)$destination,
            'docDate' => $this->convertDate($docDate),
            'docNum' => (string)$docNum,
            'docType' => (string)$docType,
            'nameA' => (string)$nameA,
            'nameB' => (string)$nameB,
            'payType' => (string)$payType,
            'paymentId' => (string)$paymentId,
            'purpose' => (string)$purpose,
            'summa' => $this->setAmountFormat($summa),
        ];
        $this->addParam('pRepaymentDetArray', $this->repayments);
    }

    public function url(): string
    {
        return $this->baseUrl . 'katm-api/v1/credit/registration/repayment/bank/details';
    }

}
