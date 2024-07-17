<?php

namespace App\Classes\Universal\Autopayment;

class Debtors extends BaseAutopayment
{

    public function __construct($pageSize = 10, $pageNumber = 0)
    {
        parent::__construct();
        $this->makeRequest('clients.get');
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
