<?php

namespace App\Classes\CURL\MLScore;

use App\Classes\ApiResponses\MLScore\MLScoreApprovalResponse;

class MLScoreUserApprovalRequest extends BaseMLWithTokenRequest
{

    public function __construct(int $userID)
    {
        parent::__construct();
        $this->addParamByKey("user_id", $userID);
    }

    public function url(): string
    {
        return $this->baseUrl . 'user/approval';
    }

    public function response(): MLScoreApprovalResponse
    {
        return new MLScoreApprovalResponse($this->responseBody->json());
    }

}
