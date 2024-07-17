<?php

namespace App\Aggregators\DebtCollectActionsAggregators;

use App\Models\DebtCollect\Debtor;
use App\Models\Record;
use App\Models\User;

class CollectorActionsAggregator
{
    public function getByDebtor($debtor_id): array
    {
        return $this->aggregateData(Debtor::find($debtor_id)->actions()->get());
    }

    public function getByPartnerContracts($contract_id): array
    {
        return $this->aggregateData(Record::whereIn('contract_id', $contract_id)->get());
    }

    public function getByContract($contract_id): array
    {
        return $this->aggregateData(Record::where('contract_id', $contract_id)->get());
    }

    private function aggregateData($data): array
    {
        $res = [];
        foreach ($data as $action) {
            $res[] = [
                "user" => User::find($action->user_id)->fio,
                "contract_id" => $action->contract_id,
                "type" => null,
                "content" => $action->text,
                "created_at" => $action->created_at->format('Y-m-d H:i:s'),
                "payment_date" => null,
                "payment_value" => null,
                "call_result" => null,
                "phone" => null,
                "info" => null,
                "files" => null
            ];
        }

        return $res;
    }
}