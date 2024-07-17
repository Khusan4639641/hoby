<?php

namespace App\Jobs\KatmReports;

use App\Classes\CURL\Katm\Accounting\ToReports\KatmMfoRequestSaveToReport;
use App\Facades\KATM\RepKatm;
use App\Facades\KATM\SaveKatm;
use App\Models\Contract;
use App\Models\KatmReceivedReport;
use App\Models\KatmReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class RegStartReportJob extends BaseKatmQuickReportJob
{

    protected function jobInfo(): string
    {
        return "Регистрация заявки (START)";
    }

    private string $token;

    protected function repeatProcess(): void
    {
        self::dispatch()->delay(now()->addMinute());
    }

    /**
     * @throws \Exception
     */
    protected function handleProcess(): void
    {
        $this->report = KatmReport::status(KatmReport::STATUS_AWAIT)
            ->sorted()
            ->where('contract_id', $this->contractID)
            ->where('report_type', KatmReport::TYPE_PRE_REGISTRATION)
            ->where('report_number', KatmReport::NUMBER_START)
            ->first();
        if (!$this->report) {
            throw new \Exception("Отчёты не найдены");
        }

        $body = json_decode($this->report->body, true);
        $request = new KatmMfoRequestSaveToReport($body);

        if (!$request) {
            $reportID = $this->report->id;
            throw new \Exception("Не удалось сформировать запрос для отчёта (ID: $reportID)");
        }

        $request->execute();
        $this->success = $request->isNeedToRepeat();
        if (!$this->success) {
            $reportID = $this->report->id;
            throw new \Exception("Не удалось получить отчёт от сервиса (ID: $reportID) " . $request->response()->text());
        } else {
            $this->token = $request->response()->token();
        }

    }

    protected function onSuccess(): void
    {
        $this->report->update([
            'status' => KatmReport::STATUS_COMPLETE,
            'sent_date' => Carbon::now()->toDateTimeString(),
        ]);

        $contract = Contract::find($this->contractID);
        SaveKatm::saveToken($contract, KatmReceivedReport::TYPE_START, $this->token);
        RepKatm::regStartStatusReport($contract, $this->token);
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
