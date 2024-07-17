<?php

namespace App\Classes\ApiResponses\Katm;

use App\Classes\ApiResponses\BaseResponse;
use Illuminate\Http\Client\Response;

class KatmResponse extends BaseResponse
{

    public function __construct(array $response)
    {
        parent::__construct($response);
        $this->fixErrorText();
    }

    private function fixErrorText()
    {
        $data = $this->json();
        if (!isset($data['data'])) {
            return;
        }
        if (!isset($data['data']['resultMessage'])) {
            return;
        }
        $this->failedMessage = $data['data']['resultMessage'];
    }

}
