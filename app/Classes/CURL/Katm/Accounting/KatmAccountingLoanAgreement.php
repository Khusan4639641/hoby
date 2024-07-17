<?php

namespace App\Classes\CURL\Katm\Accounting;

class KatmAccountingLoanAgreement extends KatmAccounting
{

//    004

    public function __construct(
        $claimID,
        $contractID,
        $inn,
        $nibbd, // user.nibbd
        $typeCode, // mfo_settings.loan_type_code 32
        $objectCode, // mfo_settings.credit_object_code 34
        $startDate,
        $endDate,
        $amount, // Сумма кредита (в тийинах)
        $currency // mfo_settings.currency_code // 000 UZS
    )
    {
        parent::__construct();

        $this->addParam('pClaimId', (string)$claimID);
        $this->addParam('pContractId', (string)$contractID);
        $this->addParam('pInn', (string)$inn);
        $this->addParam('pNibbd', (string)$nibbd);
        $this->addParam('pType', (string)$typeCode);
        $this->addParam('pObject', (string)$objectCode);
        $this->addParam('pStartDate', $this->convertDate($startDate));
        $this->addParam('pEndDate', $this->convertDate($endDate));
        $this->addParam('pCreditAmount', $this->setAmountFormat($amount));
        $this->addParam('pCurrency', (string)$currency);
        $this->addParam('pPercent', "0");
        $this->addParam('pJuridicalNumber', "");
        $this->addParam('pSupply', "");
        $this->addParam('pQuality', "");
        $this->addParam('pUrgency', "");
        $this->addParam('pHBranch', "");
        $this->addParam('pActivity', "1");
        $this->addParam('pReason', "");
        $this->addParam('pFounder', "");
        $this->addParam('pDate', $this->convertDate(date('Y-m-d H:i:s')));

    }

    public function url(): string
    {
        return $this->baseUrl . 'katm-api/v1/credit/registration';
    }

}
