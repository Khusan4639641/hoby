<?php

namespace App\Jobs\KatmReports;

use App\Classes\CURL\Katm\Accounting\ToReports\KatmAccountingBalanceToReport;
use App\Classes\CURL\Katm\Accounting\ToReports\KatmAccountingLoanAgreementToReport;
use App\Classes\CURL\Katm\Accounting\ToReports\KatmAccountingPaymentsToReport;
use App\Classes\CURL\Katm\Accounting\ToReports\KatmAccountingRefuseToReport;
use App\Classes\CURL\Katm\Accounting\ToReports\KatmAccountingRepaymentScheduleToReport;
use App\Classes\CURL\Katm\Accounting\ToReports\KatmAccountingStatusToReport;
use App\Classes\Exceptions\KatmException;
use App\Classes\Exceptions\KatmReportException;
use App\Models\KatmReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class StackKatmReportJob extends BaseKatmReportJob
{

    private const DELAY_TIME = 5; // minutes

    protected bool $isNeedToRepeat;

    protected function repeatProcess(int $delayMinutes = 0): void
    {
        self::dispatch()->delay(now()->addMinutes($delayMinutes));
    }

    protected function jobInfo(): string
    {
        return "Отчёты по проводкам";
    }

    /**
     * @throws \Exception
     */
    protected function getReport(): KatmReport
    {
        $ids = $this->getContractsFromCache();
        $report = KatmReport::status(KatmReport::STATUS_AWAIT)
            ->sorted()
            ->whereNotIn('contract_id', $ids)
            ->where('report_type', '!=', KatmReport::TYPE_PRE_REGISTRATION)
            ->first();

        if (!$report) {
            $this->isNeedToRepeat = false;
            throw new \Exception("Отчёты не найдены");
        }
        return $report;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->isNeedToRepeat = true;
        try {

            $report = $this->getReport();

            $this->rememberContractInCache($report->contract_id);

            $request = null;
            $body = json_decode($report->body, true);
            switch ($report->report_number) {
                case KatmReport::NUMBER_REFUSE:
                    $request = new KatmAccountingRefuseToReport($body);
                    break;
                case KatmReport::NUMBER_LOAN_AGREEMENT:
                    $request = new KatmAccountingLoanAgreementToReport($body);
                    break;
                case KatmReport::NUMBER_SCHEDULES:
                    $request = new KatmAccountingRepaymentScheduleToReport($body);
                    break;
                case KatmReport::NUMBER_BALANCES:
                    $request = new KatmAccountingBalanceToReport($body);
                    break;
                case KatmReport::NUMBER_PAYMENTS:
                    $request = new KatmAccountingPaymentsToReport($body);
                    break;
                case KatmReport::NUMBER_ACCOUNTS_STATUSES:
                    $request = new KatmAccountingStatusToReport($body);
                    break;
            }

            if (!$request) {
                throw new \Exception("Не удалось сформировать запрос для отчёта (ID: $report->id)");
            }

            try {

                $request->execute();
                if ($request->isSuccessful()) {
                    $report->update([
                        'status' => KatmReport::STATUS_COMPLETE,
                        'sent_date' => Carbon::now()->toDateTimeString(),
                    ]);
                    $this->forgetContractFromCache($report->contract_id);
                    $this->repeatProcess();
                }

                Log::channel('katm_report')->info($report->body);

            } catch (KatmException $e) {
                $data = [
                    'url' => $e->urlText(),
                    'code' => $e->getCode(),
                    'request' => $e->requestArray(),
                    'response' => $e->responseArray(),
                ];
                $report->update([
                    'error_response' => $e->getMessage() . " " . json_encode($data),
                    'status' => KatmReport::STATUS_BROKEN,
                    'sent_date' => Carbon::now()->toDateTimeString(),
                ]);
                $contract = $report->contract;
                $info = $this->jobInfo();
                Log::channel('katm_report')->error("[ID контракта: $contract->id][ID отчёта: $report->id][$info]: " . $e->getMessage());
                Log::channel('katm_report')->error("[ID контракта: $contract->id][ID отчёта: $report->id][$info]: Не удалось отправить текеущий запрос", $data);
                if ($this->isNeedToRepeat) {
                    $this->repeatProcess(self::DELAY_TIME);
                }
            } catch (\Throwable $e) {
                $report->update([
                    'error_response' => $e->getMessage(),
                    'status' => KatmReport::STATUS_BROKEN,
                    'sent_date' => Carbon::now()->toDateTimeString(),
                ]);
                $contract = $report->contract;
                $info = $this->jobInfo();
                Log::channel('katm_report')->error("[ID контракта: $contract->id][ID отчёта: $report->id][$info]: Не удалось отправить текеущий запрос");
                Log::channel('katm_report')->error("[ID контракта: $contract->id][ID отчёта: $report->id][$info]: " . $e->getMessage());
                Log::channel('katm_report')->error("[ID контракта: $contract->id][ID отчёта: $report->id][$info]: " . $report->body);
                $this->repeatProcess(self::DELAY_TIME);
            }
        } catch (KatmReportException $e) {
            if ($this->isNeedToRepeat) {
                $this->repeatProcess(self::DELAY_TIME);
            }
            Log::channel('katm_report')->error("Ошибка формирования отчёта");
            Log::channel('katm_report')->error($e->getMessage(), $e->getData());
        } catch (\Throwable $e) {
            if ($this->isNeedToRepeat) {
                $this->repeatProcess(self::DELAY_TIME);
            }
            Log::channel('katm_report')->error("Неожиданная ошибка");
            Log::channel('katm_report')->error($e->getMessage());
        }

    }
}
