<?php

namespace App\Classes\CURL\Katm;

class KatmRequestClaimRegistrationExt extends KatmRequest
{

    public function __construct(int    $userID,
                                string $name,
                                string $surname,
                                string $patronymic,
                                string $mrz,
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

        $this->addParam('pAddress', $address);
        $this->addParam('pAgreementDate', $utcCurTime);
        $this->addParam('pAgreementId', $agreementID);
        $this->addParam('pClaimDate', $utcCurTime);
        $this->addParam('pCreditAmount', 0);
        $this->addParam('pCreditEndDate', $utcCurTime);
        $this->addParam('pCurrency', self::CURRENCY);
        $this->addParam('pDocType', $passportType);
        $this->addParam('pLocalRegion', $localRegion);
        $this->addParam('pPhone', $phone);
        $this->addParam('pRegion', $region);
        $this->addParam('pFirstName', $name);
        $this->addParam('pLastName', $surname);
        $this->addParam('pMiddleName', $patronymic);
        $this->addParam('pMrz', $mrz);
//        $this->addParam('pIsUpdate', $this->updateState);

    }

    public function url(): string
    {
        return $this->baseUrl . 'claim/registration/ext';
    }

}


