<?php

namespace App\Classes\ApiResponses\Katm\Reports;

use App\Classes\Exceptions\KatmException;

class KatmResponseMibReport extends KatmResponseReport
{

    /**
     * @throws KatmException
     */
    public function totalDebt(): float
    {
        $data = $this->info();
        if (!isset($data['report'])) {
            throw new KatmException("Элемент report не найден", "", [], $data);
        }
        if (!isset($data['report']['allDebtSum'])) {
            return 0;
        }
        return $data['report']['allDebtSum'];
    }

}
