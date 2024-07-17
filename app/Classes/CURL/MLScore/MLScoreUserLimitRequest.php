<?php

namespace App\Classes\CURL\MLScore;

use App\Classes\ApiResponses\MLScore\MLScoreLimitResponse;

class MLScoreUserLimitRequest extends BaseMLWithTokenRequest
{

    public function __construct(int $userID)
    {
        parent::__construct();
        $this->addParamByKey("user_id", $userID);
    }

    public function url(): string
    {
        return $this->baseUrl . 'user/limit';
    }

    public function response(): MLScoreLimitResponse
    {
        return new MLScoreLimitResponse($this->responseBody->json());
    }

}
