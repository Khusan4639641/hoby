<?php

namespace App\Classes\ApiResponses\Katm\Reports;

use App\Classes\Exceptions\KatmException;
use Carbon\Carbon;

class KatmResponseInfoscoreReport extends KatmResponseReport
{

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
    public function overdueDays(): float
    {
        $data = $this->reportData();
        if (!isset($data['overview'])) {
            throw new KatmException("Элемент overview не найден", "", [], $data);
        }
        return $data['overview']['max_overdue_principal_days'] ?? 0;
    }


    /**
     * @throws KatmException
     */
    public function overdueSum(): float
    {
        $data = $this->reportData();
        if (!isset($data['overview'])) {
            throw new KatmException("Элемент overview не найден", "", [], $data);
        }
        return $data['overview']['max_overdue_principal_sum'] ?? 0;
    }

    private function makeArray(array $arr, string $key): array
    {
        if (!isset($arr[$key]) || !is_array($arr[$key])) {
            $arr[$key] = [];
        }
        return $arr;
    }

    private function normalizeStructure(array $arr, string $key): array
    {
        if (isset($arr[$key])) {
            if (!isset($arr[$key][0])) {
                return [0 => $arr[$key]];
            }
            return $arr[$key];
        }
        return [];
    }

    /**
     * @throws KatmException
     */
    public function maxOverdueDaysOfLastYears(int $year = 1): int //3
    {
        $data = $this->reportData();
        $data = $this->makeArray($data, 'contracts');
        $data['contracts'] = $this->normalizeStructure($data['contracts'], 'contract');
        if (count($data['contracts']) === 0) {
            return 0;
        }
        $data['contracts'] = collect($data['contracts'])->map(function ($item, $key) {
            $item = $this->makeArray($item, 'overdue_principals');
            $item['overdue_principals'] = $this->normalizeStructure($item['overdue_principals'], 'overdue_principal');
            return $item;
        })->all();

        $daysArr = collect($data['contracts'])
            ->pluck('overdue_principals')
            ->collapse()
            ->filter(function ($value, $key) use ($year) {
                return Carbon::now()->diffInYears($value['overdue_date']) < $year;
            })
            ->pluck('overdue_principal_days');
        if ($daysArr->count() === 0) {
            return 0;
        }
        return $daysArr->max();
    }

    /**
     * @throws KatmException
     */
    public function monthlyAveragePaymentSum(): float
    {
        $data = $this->reportData();
        $data = $this->makeArray($data, 'open_contracts');
        $data['open_contracts'] = $this->normalizeStructure($data['open_contracts'], 'open_contract');
        return collect($data['open_contracts'])
            ->pluck('monthly_average_payment')
            ->sum();
    }

    /**
     * @throws KatmException
     */
    public function pawnshopClaimsCountLastYear(): int
    {
        $data = $this->reportData();
        $data = $this->makeArray($data, 'credit_requests');
        $data['credit_requests'] = $this->normalizeStructure($data['credit_requests'], 'credit_request');
        return collect($data['credit_requests'])
            ->filter(function ($value, $key) {
                return Carbon::now()->diffInYears($value['demand_date_time']) < 1
                    && $value['org'] === "LOM";
            })
            ->count();
    }

}
