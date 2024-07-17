<?php

namespace App\Classes\CURL\MLScore;

class MLScoreBaseLimit extends BaseMLV2Request
{

    public function __construct(
        string $callbackUrl,
        int    $scoringRequestID,
        int    $userID,
        string $passportSeries,
        string $passportNumber,
        string $pinfl,
        int    $passportType,
        string $phone,
        string $address,
        int    $region,
        int    $localRegion,
        int    $gender,
        string $birthDate,
        string $inn,
        //bool   $isAuto = true,
        string $name = '',
        string $surname = '',
        string $patronymic = '',
        string $mrz = '',
        string $issueDocDate = '',
        string $expiredDocDate = ''
    )
    {
        parent::__construct();
        $this->addParamByKey('callback_url', $callbackUrl);
        $this->addParamByKey('scoring_request_id', $scoringRequestID);
        $this->addUserParam("user_id", $userID);
        $this->addUserParam("pinfl", $pinfl);
        $this->addUserParam("passport_type", $passportType);
        $this->addUserParam("local_region", $localRegion);
        $this->addUserParam("passport_series", $passportSeries);
        $this->addUserParam("passport_number", $passportNumber);
        $this->addUserParam("region", $region);
        $this->addUserParam("phone", $phone);

        $this->addUserParam("inn", $inn);
        $this->addUserParam("gender", $gender);
        $this->addUserParam("birth_date", $birthDate);
        $this->addUserParam("name", $name);
        $this->addUserParam("surname", $surname);
        $this->addUserParam("issue_doc_date", $issueDocDate);
        $this->addUserParam("expiry_doc_date", $expiredDocDate);
        $this->addUserParam("address", $address);

        /*$this->addParamByKey('callback_url', $callbackUrl);
        $this->addParamByKey('scoring_request_id', $scoringRequestID);
        $this->addUserParam("user_id", $userID);
        $this->addUserParam("passportSeries", $passportSeries);
        $this->addUserParam("passportNumber", $passportNumber);
        $this->addUserParam("pinfl", $pinfl);
        $this->addUserParam("passportType", $passportType);
        $this->addUserParam("phone", $phone);
        $this->addUserParam("address", $address);
        $this->addUserParam("region", $region);
        $this->addUserParam("localRegion", $localRegion);
        $this->addUserParam("inn", $inn);
        $this->addUserParam("gender", $gender);
        $this->addUserParam("birthDate", $birthDate);
        $this->addUserParam("isAuto", $isAuto);
        $this->addUserParam("name", $name);
        $this->addUserParam("surname", $surname);
        $this->addUserParam("patronymic", $patronymic);
        $this->addUserParam("mrz", $mrz);*/
//        $this->addUserParam("issueDocDate", $issueDocDate);
//        $this->addUserParam("expiredDocDate", $expiredDocDate);
    }

    public function url(): string
    {
        return $this->baseUrl . 'scoring/base_limit/';
    }

}
