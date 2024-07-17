<?php

namespace App\Classes\Universal\Autopayment;

class DailyPayments extends BaseAutopayment
{

    public function __construct($date, $pageSize = 10, $pageNumber = 0)
    {
        parent::__construct();
        $this->makeRequest('get.payments');
        $this->addParamByKey('date', $date);
        $this->addParamByKey('page_size', $pageSize);
        $this->addParamByKey('page_number', $pageNumber);
    }

    public function pageSize($pageSize)
    {
        $this->addParamByKey('page_size', $pageSize);
        return $this;
    }

    public function pageNumber($pageNumber)
    {
        $this->addParamByKey('page_number', $pageNumber);
        return $this;
    }

}
