<?php

namespace App\Classes\Autopay\UZCARD;

use Illuminate\Support\Carbon;

class AutopayTransactionsHistoryRequest extends BaseAutopayRequest
{

    public function __construct($dateFromText, $dateToText)
    {
        $dateFrom = new Carbon($dateFromText);
        $dateTo = new Carbon($dateToText);
        parent::__construct();
        $this->makeRequest('autopay.trans.history');
        $this->addParamByKey('range', [
            'startDate' => $dateFrom->format('Ymd'),
            'endDate' => $dateTo->format('Ymd'),
        ]);
    }

}
