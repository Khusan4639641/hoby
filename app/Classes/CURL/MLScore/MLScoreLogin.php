<?php

namespace App\Classes\CURL\MLScore;

use App\Classes\ApiResponses\MLScore\MLScoreApprovalResponse;
use App\Classes\ApiResponses\MLScore\MLScoreLoginResponse;
use Illuminate\Support\Facades\Http;

class MLScoreLogin extends BaseMLRequest
{

    public function __construct()
    {
        parent::__construct();
        $this->addParamByKey("username", config('test.ml.v1.login'));
        $this->addParamByKey("password", config('test.ml.v1.password'));
    }

    public function url(): string
    {
        return $this->baseUrl . 'login/access-token';
    }

    public function execute()
    {
        $response = Http::asForm()
            ->post($this->url(), $this->requestBody);
        $this->responseBody = $response;
        return $this;
    }

    public function response(): MLScoreLoginResponse
    {
        return new MLScoreLoginResponse($this->responseBody->json());
    }

}
