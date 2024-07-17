<?php

namespace App\Classes\ApiResponses\Katm;

class KatmResponseCreditReport extends KatmResponse
{

    public function token(): string
    {
        $data = $this->json();
        if (!isset($data['data'])) {
            return '';
        }
        if (!isset($data['data']['token'])) {
            return '';
        }
        return $data['data']['token'];
    }

}
