<?php

namespace App\Classes\CURL\Katm\Accounting;

use App\Classes\ApiResponses\Katm\KatmLoanRegistrationResponse;
use Carbon\Carbon;

class KatmLoanRegistration extends BaseRequest
{

//    001

    /**
     * @throws \Exception
     */
    public function __construct(
        $claimID,
        $number, // contract.id
        $claimDate,
        $confirmedDate, // contract.confirmed_at - 'd.m.Y'
        $inn,
        $passportType, // buyer_personals.passport_type
        $passportSerial, // buyer_personals.passport_number
        $passportNumber, // buyer_personals.passport_number
        $passportDate, // buyer_personals.passport_date_issue
        $gender, // user.gender
        $clientType, // mfo_settings.subject_type_code = 2
        $birthDate, // user.birth_date
        $nibbd, // user.nibbd
        $surname,
        $name,
        $patronymic,
        $region,
        $localRegion,
        $address,
        $phone,
        $pinfl
    )
    {
        $this->baseUrl = config('test.katm_reg.base_url');
        $this->makeRequest();

        $this->addParam('claim_id', (string)$claimID);
        $this->addParam('claim_date', Carbon::parse($claimDate)->format("d.m.Y"));
        $this->addParam('inn', (string)$inn);
        $this->addParam('claim_number', (string)$number);
        $this->addParam('agreement_number', (string)$number);
        $this->addParam('agreement_date', Carbon::parse($confirmedDate)->format("d.m.Y"));
        $this->addParam('resident', (string)1);
        $this->addParam('document_type', (string)$passportType);
        $this->addParam('document_serial', (string)$passportSerial);
        $this->addParam('document_number', (string)$passportNumber);
        $this->addParam('document_date', Carbon::parse($passportDate)->format("d.m.Y"));
        $this->addParam('gender', (string)$gender);
        $this->addParam('client_type', (string)$clientType);
        $this->addParam('birth_date', Carbon::parse($birthDate)->format("d.m.Y"));
        $this->addParam('document_region', '');
        $this->addParam('document_district', '');
        $this->addParam('nibbd', (string)$nibbd);
        $this->addParam('family_name', (string)$surname);
        $this->addParam('name', (string)$name);
        $this->addParam('patronymic', (string)$patronymic);
        $this->addParam('registration_region', (string)$region);
        $this->addParam('registration_district', (string)$localRegion);
        $this->addParam('registration_address', (string)$address);
        $this->addParam('phone', (string)$phone);
        $this->addParam('pin', $pinfl);
        $this->addParam('katm_sir', '');
        $this->addParam('live_address', (string)$address);
        $this->addParam('live_cadastr', '');
        $this->addParam('registration_cadastr', '');
    }

    public function url(): string
    {
        return $this->baseUrl . 'inquiry/individual';
    }

    protected function addParam($key, $value): void
    {
        $this->requestBody['request'][$key] = $value;
    }

    protected function makeRequest()
    {
        $this->requestBody = [
            'header' => [
                'type' => config('test.katm_reg.header.type'),
                'code' => config('test.katm_reg.header.code'),
            ],
        ];
    }

    public function response(): KatmLoanRegistrationResponse
    {
        return new KatmLoanRegistrationResponse($this->responseBody->json());
    }

}
