<?php

namespace App\Aggregators\DebtCollectActionsAggregators;

use App\Models\DebtCollect\AddedActionHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DeltaActionsAggregator
{
    public function getByDebtor($debtor_id): array
    {
        $res = [];
        try {
            $res = $this->aggregateData(AddedActionHistory::whereClient($debtor_id)->whereNotIn('ActionResult', ['Autodial', 'Звонок - SKIP'])->get());
        } catch (\Throwable $throwable){
            Log::error($throwable->getMessage(), [__METHOD__]);
        }
        return $res;
    }

    public function getByPartnerContracts($contract_ids): array
    {
        $res = [];
        try {
            $res = $this->aggregateData(AddedActionHistory::whereIn('Contract', $contract_ids)->whereNotIn('ActionResult', ['Autodial', 'Звонок - SKIP'])->get());
        } catch (\Throwable $throwable){
            Log::error($throwable->getMessage(), [__METHOD__]);
        }
        return $res;
    }

    public function getByContract($contract_id): array
    {
        $res = [];
        try {
            $res = $this->aggregateData(AddedActionHistory::where('Contract', $contract_id)->whereNotIn('ActionResult', ['Autodial', 'Звонок - SKIP'])->get());
        } catch (\Throwable $throwable){
            Log::error($throwable->getMessage(), [__METHOD__]);
        }
        return $res;
    }

    private function aggregateData($data): array
    {
        $res = [];
        foreach ($data as $item) {
            $res[] = [
                "user" => $item->Operator,
                "contract_id" => $item->Contract,
                "type" => "delta",
                "content" => $item->Text,
                "created_at" => Carbon::parse($item->Created)->format('Y-m-d H:i:s'),
                "payment_date" => $item->PaymentDate ? Carbon::parse($item->PaymentDate)->format('Y-m-d') : null,
                "payment_value" => round($item->PaymentValue, 2),
                "call_result" => $item->CallResult,
                "phone" => $item->PhoneNumber,
                "info" => $item->Info,
                "files" => null
            ];
        }

        return $res;
    }

}