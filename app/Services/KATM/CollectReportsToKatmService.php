<?php

namespace App\Services\KATM;

use App\Classes\CURL\Katm\Accounting\ToReports\KatmLoanRegistrationToReport;
use App\Classes\CURL\Katm\Accounting\ToReports\KatmMfoRequestSaveToReport;
use App\Classes\Exceptions\KatmException;
use App\Classes\Exceptions\KatmReportException;
use App\Facades\KATM\CollectDataToKatm;
use App\Facades\KATM\MakeRepKatm;
use App\Facades\KATM\SaveKatm;
use App\Jobs\KatmReports\RegStartStatusReportJob;
use App\Models\AccountingEntry;
use App\Models\Contract;
use App\Models\GeneralCompany;
use App\Models\KatmReceivedReport;
use App\Models\KatmReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

class CollectReportsToKatmService
{

    private $logChannel;
    private $logMessage = "";
    private $logData = [];

    public function __construct()
    {
        $this->logChannel = Log::channel('katm_report');
    }

    private function infoLog(string $info, array $data = []): void
    {
        $this->logMessage = $info;
        $this->logData = $data;
        if (config('app.env') === 'prod'
            || config('app.env') === 'production') {
            return;
        }
        $this->logChannel->info($info, $data);
    }

    private function errorLog(string $errorInfo, array $errorData = []): void
    {
        $this->logChannel->error("Обнаружена ошибка при формированиие KATM отчётов");
        $this->logChannel->error($errorInfo, $errorData);
        $this->logChannel->error("Дополнительная информация");
        $this->logChannel->error($this->logMessage, $this->logData);
    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function regLoanAppAndSend(Contract $contract): void
    {
        try {
            $generalCompany = $contract->generalCompany;
            if ($generalCompany->type !== GeneralCompany::TYPE_MFI) {
                $this->infoLog("[ID контракта: $contract->id]: Контракт не относиться к МФО");
                return;
            }
            $katmReport = MakeRepKatm::report001($contract);
            if ($katmReport->status !== KatmReport::STATUS_COMPLETE) {
                $this->infoLog("[ID контракта: $contract->id]: Отчёт 001 - подготовка к отправке");

                try {
                    $body = json_decode($katmReport->body, true);
                    $request = new KatmLoanRegistrationToReport($body);

                    $request->execute();
                    if (!$request->isSuccessful()) {
                        throw new \Exception("Не удалось получить отчёт от сервиса (ID: $katmReport->id) " . $request->response()->text());
                    }

                    $katmReport->update([
                        'status' => KatmReport::STATUS_COMPLETE,
                        'sent_date' => Carbon::now()->toDateTimeString(),
                    ]);

                    $this->infoLog("[ID контракта: $contract->id]: Отчёт 001 отправлен");
                } catch (KatmException | Throwable $e) {
                    $message = $e->getMessage();
                    $trace = collect($e->getTrace())->take(10);
                    $data = [];
                    if ($e instanceof KatmException) {
                        $data = [
                            'url' => $e->urlText(),
                            'request' => $e->requestArray(),
                            'response' => $e->responseArray(),
                        ];
                    }
                    if (!$katmReport) {
                        Log::channel('katm_report')->error("[ID контракта: $contract->id]: Не удалось отправить текеущий запрос", $data);
                        Log::channel('katm_report')->error("[ID контракта: $contract->id]: $message");
                        foreach ($trace as $line) {
                            Log::channel('katm_report')->error($line);
                        }
                        return;
                    }
                    $katmReport->update([
                        'error_response' => $message,
                        'status' => KatmReport::STATUS_BROKEN,
                        'sent_date' => Carbon::now()->toDateTimeString(),
                    ]);
                    $info = $this->jobInfo();
                    Log::channel('katm_report')->error("[ID контракта: $contract->id][ID отчёта: $katmReport->id][$info]: Не удалось отправить текеущий запрос", $data);
                    Log::channel('katm_report')->error("[ID контракта: $contract->id][ID отчёта: $katmReport->id][$info]: $message");
                    foreach ($trace as $line) {
                        Log::channel('katm_report')->error($line);
                    }
                }
            }
        } catch (KatmReportException $e) {
            $this->errorLog($e->getMessage(), $e->getData());
        } catch (Throwable $e) {
            $this->errorLog("[ID контракта: $contract->id, Процесс формирования отчёта 001]: " . $e->getMessage());
        }

    }

    public function regStartReport(Contract $contract): void
    {
        try {
            $katmReport = MakeRepKatm::reportStart($contract);
            if ($katmReport->status !== KatmReport::STATUS_COMPLETE) {
                $this->infoLog("[ID контракта: $contract->id]: Отчёт START - подготовка к отправке");

//                RegStartReportJob::dispatch($contract->id);

                // @todo Временное решение
                try {

                    $report = $contract->katmReport()
                        ->statusNot(KatmReport::STATUS_COMPLETE)
                        ->where('report_type', KatmReport::TYPE_PRE_REGISTRATION)
                        ->where('report_number', KatmReport::NUMBER_START)
                        ->first();

                    $body = json_decode($report->body, true);
                    $request = new KatmMfoRequestSaveToReport($body);

                    if (!$request) {
                        throw new \Exception("Не удалось сформировать запрос для отчёта (ID: $report->id)");
                    }

                    $request->execute();
                    if (!$request->isNeedToRepeat()) {
                        throw new \Exception("Не удалось получить отчёт от сервиса (ID: $report->id) " . $request->response()->text());
                    } else {
                        $this->token = $request->response()->token();
                    }

                    $report->update([
                        'status' => KatmReport::STATUS_REPEAT,
                        'sent_date' => Carbon::now()->toDateTimeString(),
                    ]);

                    SaveKatm::saveToken($contract, KatmReceivedReport::TYPE_START, $this->token);
                    $this->infoLog("[ID контракта: $contract->id]: Отчёт START отправлен");

                    $this->regStartStatusReport($contract, $this->token);

                } catch (KatmException | Throwable $e) {
                    $message = $e->getMessage();
                    $trace = collect($e->getTrace())->take(10);
                    $data = [];
                    if ($e instanceof KatmException) {
                        $data = [
                            'url' => $e->urlText(),
                            'request' => $e->requestArray(),
                            'response' => $e->responseArray(),
                        ];
                    }
                    if (!$report) {
                        Log::channel('katm_report')->error("[ID контракта: $contract->id]: Не удалось отправить текеущий запрос", $data);
                        Log::channel('katm_report')->error("[ID контракта: $contract->id]: $message");
                        foreach ($trace as $line) {
                            Log::channel('katm_report')->error($line);
                        }
                        return;
                    }
                    $report->update([
                        'error_response' => $message,
                        'status' => KatmReport::STATUS_BROKEN,
                        'sent_date' => Carbon::now()->toDateTimeString(),
                    ]);
                    $contract = $report->contract;
                    $info = "Регистрация заявки (START)";
                    Log::channel('katm_report')->error("[ID контракта: $contract->id][ID отчёта: $report->id][$info]: Не удалось отправить текеущий запрос", $data);
                    Log::channel('katm_report')->error("[ID контракта: $contract->id][ID отчёта: $report->id][$info]: $message");
                    foreach ($trace as $line) {
                        Log::channel('katm_report')->error($line);
                    }
                }

            }
        } catch (KatmReportException $e) {
            $this->errorLog($e->getMessage(), $e->getData());
        } catch (Throwable $e) {
            $this->errorLog("[ID контракта: $contract->id, Процесс формирования отчёта START]: " . $e->getMessage());
        }
    }

    public function regStartStatusReport(Contract $contract, string $token): void
    {
        try {
            $katmReport = $contract->katmReport()
                ->sorted()
                ->where('report_type', KatmReport::TYPE_PRE_REGISTRATION)
                ->where('report_number', KatmReport::NUMBER_START)
                ->where('status', KatmReport::STATUS_REPEAT)
                ->exists();
            if ($katmReport) {
                $this->infoLog("[ID контракта: $contract->id]: Отчёт START STATUS отправлен на очередь");
                RegStartStatusReportJob::dispatch($contract->id, $token)->delay(now()->addSeconds(5));
            }
        } catch (KatmReportException $e) {
            $this->errorLog($e->getMessage(), $e->getData());
        } catch (Throwable $e) {
            $this->errorLog("[ID контракта: $contract->id, Процесс формирования отчёта START STATUS]: " . $e->getMessage());
        }
    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function processSingle(Contract $contract, $date): void
    {
        try {

            $this->infoLog("Проверка контракта");

            $exists = $contract->query()
                ->whereIn('status', [
                    Contract::STATUS_ACTIVE,
                    Contract::STATUS_OVERDUE_60_DAYS,
                    Contract::STATUS_OVERDUE_30_DAYS,
                    Contract::STATUS_CANCELED,
                    Contract::STATUS_COMPLETED
                ])
                ->whereHas('generalCompany', function ($query) {
                    $query->where('type', GeneralCompany::TYPE_MFI);
                })
                ->whereHas('accountingEntries', function ($query) {
                    $query->whereIn('destination_code', [
                        AccountingEntry::CODE_1007,
                        AccountingEntry::CODE_1008,
                        AccountingEntry::CODE_1009
                    ]);
                })
                ->exists();

            if (!$exists) {
                throw new \Exception("Контракт не имеет отношения к МФО или не имеет в наличии проводок");
            }

            $this->infoLog("Обработка контракта");

            $format = 'Ymd';
            $confirmedAt = Carbon::parse($contract->confirmed_at);
            $cancelledAt = Carbon::parse($contract->canceled_at);
            $date = Carbon::parse($date)->format($format);
            $accountingEntryForReg = $contract->accountingEntries()
                ->where('destination_code', AccountingEntry::CODE_1007)
                ->first();
            if (!$accountingEntryForReg) {
//                @todo Определить алгоритм выхода из ситуации
                throw new KatmReportException('Код 1007 не найден в проводках',
                    ['contractID' => $contract->id]
                );
            }
            $accountingEntryForRegAt = Carbon::parse($accountingEntryForReg->operation_date)->format($format);
            if ($contract->status === Contract::STATUS_CANCELED
                && $confirmedAt->format($format) === $cancelledAt->format($format)
                && $confirmedAt->format($format) === $date) {
//                @todo Определить алгоритм вычисления для отменённых контрактов
                $this->cancelContract($contract);
            } else if (($contract->status === Contract::STATUS_ACTIVE
                    || $contract->status === Contract::STATUS_OVERDUE_60_DAYS
                    || $contract->status === Contract::STATUS_OVERDUE_30_DAYS
                    || $contract->status === Contract::STATUS_COMPLETED)
                && $accountingEntryForRegAt === $date) {
                $this->regContract($contract, $date);
            } else {
                $this->sendAccEntries($contract, $date);
            }

            $this->infoLog("[ID контракта: $contract->id]: Обработан");

//            StackKatmReportByContractJob::dispatch($contract->id);

        } catch (KatmReportException $e) {
            $this->errorLog($e->getMessage(), $e->getData());
        } catch (Throwable $e) {
            $this->errorLog($e->getMessage());
        }

    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    private function registrationReports(Contract $contract, $date): void
    {
        try {
            $this->infoLog("[ID контракта: $contract->id]: Формирование отчётов контракта (START, 004, 005)");

            // make 001
            $this->regLoanAppAndSend($contract);

            // make START and START_STATUS
            $this->regStartReport($contract);

            // 004
            MakeRepKatm::report004($contract, $date);

            // 005
            MakeRepKatm::report005($contract, $date);

        } catch (KatmReportException $e) {
            $this->errorLog($e->getMessage(), $e->getData());
        } catch (Throwable $e) {
            $this->errorLog("[ID контракта: $contract->id, Процесс формирования отчётов для регистрации контракта]: " . $e->getMessage());
        }
    }

    private function entriesReports(Contract $contract, $date, $allAccounts = false): void
    {
        try {
            $this->infoLog("[ID контракта: $contract->id]: Формирование отчётов контракта (015, 016)");

            // 015
            MakeRepKatm::report015($contract, $date, $allAccounts);

            // 016
            MakeRepKatm::report016($contract, $date);

        } catch (KatmReportException $e) {
            $this->errorLog($e->getMessage(), $e->getData());
        } catch (Throwable $e) {
            $this->errorLog("[ID контракта: $contract->id, Процесс формирования отчётов по проводкам]: " . $e->getMessage());
        }
    }

    private function entriesStatusesReports(Contract $contract, $date, $type = KatmReport::TYPE_COMPLETE): void
    {
        try {
            $this->infoLog("[ID контракта: $contract->id]: Формирование отчётов контракта (018)");

            // 018
            MakeRepKatm::report018($contract, $date, $type);

        } catch (KatmReportException $e) {
            $this->errorLog($e->getMessage(), $e->getData());
        } catch (Throwable $e) {
            $this->errorLog("[ID контракта: $contract->id, Процесс формирования отчётов по проводкам]: " . $e->getMessage());
        }

    }

    public function makeDailyContractReports(Contract $contract, $date): void
    {
        if ($this->isNewContract($contract, $date)) {
            $this->registrationReports($contract, $date);
        }
        if ($this->issetEntryPayments($contract, $date)) {
            if ($this->isNewContract($contract, $date)) {
                $this->entriesReports($contract, $date, true);
            } else {
                $this->entriesReports($contract, $date);
            }
        }
        if (!$this->issetEntryStatusReports($contract, KatmReport::TYPE_REGISTRATION)) {
            $this->entriesStatusesReports($contract, $date, KatmReport::TYPE_REGISTRATION);
        }
        if (!$this->issetEntryStatusReports($contract)
            && $this->isEntriesBalanceClosed($contract, $date)) {
            $this->entriesStatusesReports($contract, $date);
        }
    }

    private function issetEntryStatusReports(Contract $contract, $type = KatmReport::TYPE_COMPLETE): bool
    {
        return $contract->katmReport()
                ->where('report_number', KatmReport::NUMBER_ACCOUNTS_STATUSES)
                ->where('report_type', $type)
                ->count() > 0;
    }

    private function isNewContract(Contract $contract, $date): bool
    {
        return (Carbon::parse($contract->confirmed_at)->format('Ymd')
                === Carbon::parse($date)->format('Ymd'))
            && ($contract->status === Contract::STATUS_ACTIVE
                || $contract->status === Contract::STATUS_OVERDUE_60_DAYS
                || $contract->status === Contract::STATUS_OVERDUE_30_DAYS
                || $contract->status === Contract::STATUS_COMPLETED);
    }

    private function issetEntryPayments(Contract $contract, $date): bool
    {
        $date = Carbon::parse($date)->format('Y-m-d');
        return $contract->accountingEntries()
                ->where('status', AccountingEntry::STATUS_ACTIVE)
                ->whereIn('destination_code', [
                    AccountingEntry::CODE_1007,
                    AccountingEntry::CODE_1008,
                    AccountingEntry::CODE_1009
                ])
                ->whereRaw("DATE(operation_date) = '$date'")
                ->count() > 0;
    }

    private function isEntriesBalanceClosed(Contract $contract, $date): bool
    {
        $accounts = CollectDataToKatm::getAccountsTypes();
        foreach ($accounts as $accountMask) {
            $accountBalance = CollectDataToKatm::getAccountBalanceToDate($contract, $accountMask, $date, false);
            if ($accountBalance > 0) {
                return false;
            }
        }
        return true;
    }

}
