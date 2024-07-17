<?php

namespace App\Services\KATM;

use App\Helpers\FileHelper;
use App\Models\Contract;
use App\Models\File;
use App\Models\KatmReceivedReport;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class SaveReportFromKatmService
{

    public function saveToken(Contract $contract, string $reportType, string $token): KatmReceivedReport
    {
        $katmReceivedReport = $this->findRow($contract, $reportType, $token);
        if (!$katmReceivedReport) {
            $katmReceivedReport = $contract->katmReceivedReport()
                ->create([
                    'token' => $token,
                    'report_type' => $reportType,
                ]);
        }
        return $katmReceivedReport->getModel();
    }

    /**
     * @throws \Exception
     */
    public function saveReport(Contract $contract, string $reportType, string $token, string $location, string $filename, array $data): void
    {
        $katmReceivedReport = $this->saveToken($contract, $reportType, $token);
        $text = json_encode($data);
        $fileID = FileHelper::uploadToPath(
            $katmReceivedReport->id,
            File::MODEL_KATM_RECEIVED_REPORT,
            File::TYPE_JSON,
            $location,
            $filename,
            $text,
            'json',
            1
        );
        if (!$fileID) {
            $data = [
                'ContractID' => $contract->id,
                'ReportType' => $reportType,
                'Token' => $token,
            ];
            throw new \Exception("Не удалось сохранить json файл в хранилище. " . json_encode($data));
        }
        $katmReceivedReport->received_date = Carbon::now()->format('Y-m-d H:i:s');
        $katmReceivedReport->status = KatmReceivedReport::STATUS_COMPLETE;
        $katmReceivedReport->file_id = $fileID;
        $katmReceivedReport->save();
    }

    public function saveReportText(Contract $contract, string $reportType, string $token, string $location, string $filename, string $data): void
    {
        $this->saveReport($contract, $reportType, $token, $location, $filename, json_decode($data, true));
    }

    /**
     * @throws \Exception
     */
    public function failedToGetReport(Contract $contract, string $reportType, string $token, string $failMessage): void
    {
        $katmReceivedReport = $this->findRow($contract, $reportType, $token);
        if (!$katmReceivedReport) {
            $data = [
                'ContractID' => $contract->id,
                'ReportType' => $reportType,
                'Token' => $token,
            ];
            throw new \Exception("Token отчёта не найден в БД. " . json_encode($data));
        }
        $katmReceivedReport->received_date = Carbon::now()->format('Y-m-d H:i:s');
        $katmReceivedReport->response_error = $failMessage;
        $katmReceivedReport->status = KatmReceivedReport::STATUS_BROKEN;
        $katmReceivedReport->save();
    }

    private function findRow(Contract $contract, string $reportType, string $token): ?KatmReceivedReport
    {
        $katmReceivedReport = $contract->katmReceivedReport()
            ->where('token', $token)
            ->where('report_type', $reportType)
            ->first();
        if (!$katmReceivedReport) {
            return null;
        }
        return $katmReceivedReport->getModel();
    }

    /**
     * @throws \Exception
     */
    public function findReport(Contract $contract, string $reportType, string $token): array
    {
        $katmReceivedReport = $this->findRow($contract, $reportType, $token);
        if (!$katmReceivedReport) {
            $data = [
                'ContractID' => $contract->id,
                'ReportType' => $reportType,
                'Token' => $token,
            ];
            throw new \Exception("Token отчёта не найден в БД. " . json_encode($data));
        }
        if (!$katmReceivedReport->file) {
            $data = [
                'ContractID' => $contract->id,
                'ReportType' => $reportType,
                'Token' => $token,
            ];
            throw new \Exception("Не найдена запись в таблице files " . json_encode($data));
        }
        $url = FileHelper::url($katmReceivedReport->file->path);
        $response = Http::get($url);
        if (!$response->successful()) {
            $data = [
                'ContractID' => $contract->id,
                'ReportType' => $reportType,
                'Token' => $token,
                'Url' => $url,
            ];
            throw new \Exception("Не удалось получить файл " . json_encode($data));
        }
        return $response->json();
    }

    public function findReportText(Contract $contract, string $reportType, string $token): string
    {
        return json_encode(self::findReport($contract, $reportType, $token));
    }
}
