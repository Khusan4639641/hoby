<?php

namespace App\Classes\ApiResponses\Katm\Reports;

use App\Classes\Exceptions\KatmException;

class KatmResponseScoringReport extends KatmResponseReport
{

    const AMOUNT_SUM_DIVIDER = 100;

    const GENDER_MALE = 'М';
    const GENDER_FEMALE = 'Ж';

    private function calculatedDebt(): float
    {
        $averageMonthly = $this->averageMonthlyPayment();
        $allDebts = $this->totalDebt();
        if ($averageMonthly == 0) {
            return 0;
        }
        $monthsCount = ceil($allDebts / $averageMonthly);
        return ($monthsCount > 12) ? ($averageMonthly * 12) : $allDebts;
    }

    public function calculatedDebtFormatted(): float
    {
        return $this->calculatedDebt() / self::AMOUNT_SUM_DIVIDER;
    }

    /**
     * @throws KatmException
     */
    private function report23Data(): array
    {
        $data = $this->info();
        if (!isset($data['report23'])) {
            throw new KatmException("Элемент report23 не найден", "", [], $data);
        }
        if (isset($data['report23'][0])) {
            return $data['report23'][0];
        }
        return $data['report23'];
    }

    /**
     * @throws KatmException
     */
    private function reportData(): array
    {
        $data = $this->info();
        if (!isset($data['report'])) {
            throw new KatmException("Элемент report не найден", "", [], $data);
        }
        return $data['report'];
    }

    /**
     * @throws KatmException
     */
    private function averageMonthlyPayment(): float
    {
        $data = $this->report23Data();
        if (!isset($data['open_contracts'])) {
            throw new KatmException("Элемент open_contracts не найден", "", [], $data);
        }
        if (!isset($data['open_contracts']['average_monthly_payment'])) {
            throw new KatmException("Элемент average_monthly_payment не найден", "", [], $data);
        }
        return $data['open_contracts']['average_monthly_payment'];
    }


    /**
     * @throws KatmException
     */
    private function overdueDebt(): float
    {
        $data = $this->report23Data();
        if (!isset($data['open_contracts'])) {
            throw new KatmException("Элемент open_contracts не найден", "", [], $data);
        }
        if (!isset($data['open_contracts']['all_overdue_debt_sum'])) {
            throw new KatmException("Элемент all_overdue_debt_sum не найден", "", [], $data);
        }
        return $data['open_contracts']['all_overdue_debt_sum'];
    }

    public function overdueDebtFormatted(): float
    {
        return $this->overdueDebt() / self::AMOUNT_SUM_DIVIDER;
    }

    /**
     * @throws KatmException
     */
    private function totalDebt(): float
    {
        $data = $this->report23Data();
        if (!isset($data['open_contracts'])) {
            throw new KatmException("Элемент open_contracts не найден", "", [], $data);
        }
        if (!isset($data['open_contracts']['all_debt_sum'])) {
            throw new KatmException("Элемент all_debt_sum не найден", "", [], $data);
        }
        return $data['open_contracts']['all_debt_sum'];
    }

    /**
     * @throws KatmException
     */
    public function pawnshopClaims(): int
    {
        $data = $this->reportData();
        if (!isset($data['subject_claims'])) {
            throw new KatmException("Элемент subject_claims не найден", "", [], $data);
        }
        if (!isset($data['subject_claims']['lombard_claims'])) {
            throw new KatmException("Элемент lombard_claims не найден", "", [], $data);
        }
        if (!isset($data['subject_claims']['lombard_claims']['claims_qty'])) {
            throw new KatmException("Элемент claims_qty не найден", "", [], $data);
        }
        return $data['subject_claims']['lombard_claims']['claims_qty'];
    }

    /**
     * @throws KatmException
     */
    public function clientFullName(): string
    {
        $data = $this->reportData();
        if (!isset($data['client'])) {
            throw new KatmException("Элемент client не найден", "", [], $data);
        }
        if (!isset($data['client']['name'])) {
            throw new KatmException("Элемент name не найден", "", [], $data);
        }
        return $data['client']['name'];
    }

    /**
     * @throws KatmException
     */
    public function clientBirthdate(): string
    {
        $data = $this->reportData();
        if (!isset($data['client'])) {
            throw new KatmException("Элемент client не найден", "", [], $data);
        }
        if (!isset($data['client']['birth_date'])) {
            throw new KatmException("Элемент birth_date не найден", "", [], $data);
        }
        return date('Y-m-d', strtotime($data['client']['birth_date']));
    }

    /**
     * @throws KatmException
     */
    public function clientGender(): ?int
    {
        $data = $this->reportData();
        if (!isset($data['client'])) {
            throw new KatmException("Элемент client не найден", "", [], $data);
        }
        if (!isset($data['client']['gender'])) {
            throw new KatmException("Элемент birth_date не найден", "", [], $data);
        }
        $gender = $data['client']['gender'];
        if ($gender == self::GENDER_FEMALE) {
            return 2;
        } else if ($gender == self::GENDER_MALE) {
            return 1;
        }
        return null;
    }

    /**
     * @throws KatmException
     */
    public function clientTaxIdentificationNumber(): string
    {
        $data = $this->reportData();
        if (!isset($data['client'])) {
            throw new KatmException("Элемент client не найден", "", [], $data);
        }
        if (!isset($data['client']['inn'])) {
            throw new KatmException("Элемент inn не найден", "", [], $data);
        }
        return $data['client']['inn'];
    }

    /**
     * @throws KatmException
     */
    public function clientPassportDateIssue(): string
    {
        $data = $this->reportData();
        if (!isset($data['client'])) {
            throw new KatmException("Элемент client не найден", "", [], $data);
        }
        if (!isset($data['client']['document_date'])) {
            throw new KatmException("Элемент document_date не найден", "", [], $data);
        }
        return $data['client']['document_date'];
    }

}
