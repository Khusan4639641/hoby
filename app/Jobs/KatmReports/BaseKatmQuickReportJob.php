<?php

namespace App\Jobs\KatmReports;

use App\Classes\Exceptions\KatmException;
use Illuminate\Support\Facades\Log;

abstract class BaseKatmQuickReportJob extends BaseKatmReportJob
{

    public $queue = 'katm-report';

    protected int $contractID;

    protected $report;

    protected bool $success = false;

    public function __construct(int $contractID)
    {
        $this->contractID = $contractID;
    }

    private function logInfo(int $contractID, string $message, array $data = [])
    {
        $info = $this->jobInfo();
        Log::channel('katm_report')->info("[ID контракта: $contractID][$info]: $message", $data);
    }

    abstract protected function jobInfo(): string;

    abstract protected function repeatProcess(): void;

    abstract protected function handleProcess(): void;

    abstract protected function onSuccess(): void;

    abstract protected function onFail(string $message, array $data = [], array $trace = []): void;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $ids = $this->getContractsFromCache();

            if (in_array($this->contractID, $ids, false)) {
                $this->logInfo($this->contractID, "Отчёт обрабатывается другим процессом", $ids);
                $this->repeatProcess();
                return;
            }

            $this->logInfo($this->contractID, "Старт обработки");
            $this->handleProcess();

            $this->logInfo($this->contractID, "Кэширование ID контракта");
            $this->rememberContractInCache($this->contractID);

            if ($this->success) {
                $this->logInfo($this->contractID, "Сброс ID контракта из кэша");
                $this->forgetContractFromCache($this->contractID);
                $this->onSuccess();
            }
            $this->logInfo($this->contractID, "Завершение обработки");

        } catch (KatmException $e) {
            $data = [
                'url' => $e->urlText(),
                'request' => $e->requestArray(),
                'response' => $e->responseArray(),
            ];
            $this->onFail($e->getMessage(), $data);
        } catch (\Throwable $e) {
            $trace = collect($e->getTrace())->take(10);
            $this->onFail($e->getMessage(), [], (array)$trace);
        }
    }
}
