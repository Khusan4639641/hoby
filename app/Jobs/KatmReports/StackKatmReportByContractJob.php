<?php

namespace App\Jobs\KatmReports;

use App\Models\KatmReport;

class StackKatmReportByContractJob extends StackKatmReportJob
{

    private int $triesLimit;
    private int $contractID;
    private int $triesCount;

    public function __construct(int $contractID, $triesCount = 0)
    {
        $this->triesLimit = config('test.katm.sending_tries_limit');
        $this->contractID = $contractID;
        $this->triesCount = $triesCount;
        $this->triesCount++;
    }

    protected function jobInfo(): string
    {
        return "Отчёты по проводкам конкретного контракта";
    }

    protected function repeatProcess(int $delayMinutes = 0): void
    {
        if ($this->triesCount > $this->triesLimit) {
            return;
        }
        self::dispatch($this->contractID, $this->triesCount)->delay(now()->addMinutes($delayMinutes));
    }

    /**
     * @throws \Exception
     */
    protected function getReport(): KatmReport
    {
        $ids = $this->getContractsFromCache();

        if (in_array($this->contractID, $ids, true)) {
            throw new \Exception("Отчёты контракта (ID: $this->contractID) на обработке");
        }

        $report = KatmReport::statusNot(KatmReport::STATUS_COMPLETE)
            ->sorted()
            ->where('contract_id', $this->contractID)
            ->whereNotIn('report_number', [KatmReport::NUMBER_LOAN_REG, KatmReport::NUMBER_START])
            ->first();

        if (!$report) {
            $this->isNeedToRepeat = false;
            throw new \Exception("Отчёты не найдены");
        }

        return $report;
    }
}
