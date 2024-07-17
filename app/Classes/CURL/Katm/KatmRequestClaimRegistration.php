<?php

namespace App\Classes\CURL\Katm;

class KatmRequestClaimRegistration extends KatmRequest
{

    public function __construct(int    $userID,
                                string $passportSeries,
                                string $passportNumber,
                                string $pinfl,
                                int    $passportType,
                                string $phone,
                                int    $region,
                                int    $localRegion,
                                string $address)
    {

        $t = microtime(true);
        $micro = sprintf("%03d", ($t - floor($t)) * 1000);
        $utcCurTime = gmdate('Y-m-d\TH:i:s.', $t) . $micro . 'Z';

        $agreementID = mb_substr(md5('pm-' . time()), 1, 10);

        parent::__construct(parent::generateClaimID($userID));

        $this->addParam('pDocNumber', $passportNumber);
        $this->addParam('pDocSeries', $passportSeries);
        $this->addParam('pDocType', $passportType);
        $this->addParam('pAddress', $address);
        $this->addParam('pRegion', $region);
        $this->addParam('pLocalRegion', $localRegion);
        $this->addParam('pPhone', $phone);
        $this->addParam('pPinfl', $pinfl);
        $this->addParam('pAgreementId', $agreementID);
        $this->addParam('pClaimDate', $utcCurTime);
        $this->addParam('pAgreementDate', $utcCurTime);
        $this->addParam('pCreditEndDate', $utcCurTime);
        $this->addParam('pCreditAmount', 0);
        $this->addParam('pCurrency', self::CURRENCY);
//        $this->addParam('pIsUpdate', $this->updateState);

    }

    public function url(): string
    {
        return $this->baseUrl . 'claim/registration';
    }

}


