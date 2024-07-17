<?php

namespace App\Classes\CURL\Royxat;

use Illuminate\Support\Facades\Log;

class RoyxatCreditsSearch extends BaseRoyxatRequest
{

    public function __construct(string $passport)
    {
        parent::__construct();
        $this->requestBody['passport_id'] = $passport;
    }

    public function url(): string
    {
        return $this->baseUrl . 'credits/search';
    }

}
