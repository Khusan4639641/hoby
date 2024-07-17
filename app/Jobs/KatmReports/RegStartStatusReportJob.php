<?php

namespace App\Jobs\KatmReports;

use App\Classes\CURL\Katm\MFO\KatmMfoRequestReportStatus;
use App\Facades\KATM\SaveKatm;
use App\Models\Contract;
use App\Models\KatmReceivedReport;
use App\Models\KatmReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RegStartStatusReportJob extends BaseKatmQuickReportJob
{

    protected const DELAY_TIME = 5; // in seconds

    protected function jobInfo(): string
    {
        return "Получение заявки (START STATUS)";
    }

    private const MAX_TRIES_COUNT = 20;

    private int $tryCount;
    private string $reportToken;
    private string $reportDataText;

    public function __construct(int $contractID, string $reportToken, $tryCount = 0)
    {
        parent::__construct($contractID);
        $this->tryCount = $tryCount + 1;
        $this->reportToken = $reportToken;
    }

    protected function repeatProcess(): void
    {
        $this->forgetContractFromCache($this->contractID);
        self::dispatch($this->contractID, $this->reportToken, $this->tryCount)->delay(now()->addSeconds(self::DELAY_TIME));
    }

    /**
     * @throws \Exception
     */
    protected function handleProcess(): void
    {
        $this->report = KatmReport::sorted()
            ->where('contract_id', $this->contractID)
            ->where('report_type', KatmReport::TYPE_PRE_REGISTRATION)
            ->where('report_number', KatmReport::NUMBER_START)
            ->first();
        if (!$this->report) {
            throw new \Exception("Отчёты не найдены");
        }

        $contract = Contract::find($this->contractID);
        $claimID = $contract->katmClaim->claim;
        $request = new KatmMfoRequestReportStatus($claimID, $this->reportToken);

        if (!$request) {
            $reportID = $this->report->id;
            throw new \Exception("Не удалось сформировать запрос для отчёта (ID: $reportID)");
        }

        $request->execute();

        if ($request->isNeedToRepeat()) {
            $this->report->update([
                'status' => KatmReport::STATUS_REPEAT,
                'sent_date' => Carbon::now()->toDateTimeString(),
            ]);
            $this->repeatProcess();
            return;
        }

        $this->success = $request->isSuccessful();
        if (!$this->success) {
            $reportID = $this->report->id;
            throw new \Exception("Не удалось получить отчёт от сервиса (ID: $reportID) " . $request->response()->text());
        }

        $this->reportDataText = $request->report()->text();

    }

    protected function onSuccess(): void
    {
        SaveKatm::saveReportText(
            Contract::find($this->contractID),
            KatmReceivedReport::TYPE_START,
            $this->reportToken,
            "katm/reports/receives",
            $this->reportToken,
            $this->reportDataText
        );
        $this->report->update([
            'status' => KatmReport::STATUS_COMPLETE,
            'sent_date' => Carbon::now()->toDateTimeString(),
        ]);
    }

    protected function onFail(string $message, array $data = [], array $trace = []): void
    {
        if (!$this->report) {
            Log::channel('katm_report')->error("[ID контракта: $this->contractID]: Не удалось отправить текеущий запрос", $data);
            Log::channel('katm_report')->error("[ID контракта: $this->contractID]: $message");
            foreach ($trace as $line) {
                Log::channel('katm_report')->error($line);
            }
            return;
        }
        $this->report->update([
            'error_response' => $message,
            'status' => KatmReport::STATUS_BROKEN,
            'sent_date' => Carbon::now()->toDateTimeString(),
        ]);
        if ($this->tryCount <= self::MAX_TRIES_COUNT) {
            $this->repeatProcess();
        }
        $report = $this->report;
        $contract = $report->contract;
        $info = $this->jobInfo();
        Log::channel('katm_report')->error("[ID контракта: $contract->id][ID отчёта: $report->id][$info]: Не удалось отправить текеущий запрос", $data);
        Log::channel('katm_report')->error("[ID контракта: $contract->id][ID отчёта: $report->id][$info]: $message");
        foreach ($trace as $line) {
            Log::channel('katm_report')->error($line);
        }
    }
}
