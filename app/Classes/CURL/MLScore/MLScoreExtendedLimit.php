<?php

namespace App\Classes\CURL\MLScore;

class MLScoreExtendedLimit extends BaseMLV2Request
{

    public function __construct(
        string $callbackUrl,
        int    $scoringRequestID,
        int    $userID,
        string $pinfl,
        string $cardToken

    )
    {
        parent::__construct();
        $this->addParamByKey('callback_url', $callbackUrl);
        $this->addParamByKey('scoring_request_id', $scoringRequestID);
        $this->addUserParam("user_id", $userID);
        $this->addUserParam("pinfl", $pinfl);
        $this->addUserParam("token", $cardToken);
    }

    public function url(): string
    {
        return $this->baseUrl . 'scoring/extended_limit/';
    }

}
