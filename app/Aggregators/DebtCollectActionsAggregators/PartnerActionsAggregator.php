<?php

namespace App\Aggregators\DebtCollectActionsAggregators;

use App\Models\Contract;
use App\Models\DebtCollect\Debtor;
use App\Models\PartnerContractAction;
use App\Models\Record;
use App\Models\User;

class PartnerActionsAggregator
{
    public function getByDebtor($debtor_id): array
    {
        return $this->aggregateData(PartnerContractAction::whereIn('contract_id', Contract::whereUserId($debtor_id)
            ->whereIn('status', [3,4])
            ->get('id')
            ->toArray())
            ->get());
    }

    public function getByPartnerContracts($contract_ids): array
    {
        return $this->aggregateData(PartnerContractAction::whereIn('contract_id', $contract_ids)->get());
    }

    public function getByContract($contract_id): array
    {
        return $this->aggregateData(PartnerContractAction::where('contract_id', $contract_id)->get());
    }

    private function aggregateData($data): array
    {
        $res = [];
        foreach ($data as $action) {
            $res[] = [
                "user" => $action->company->name,
                "contract_id" => $action->contract_id,
                "type" => 'merchant',
                "content" => $action->content,
                "created_at" => $action->created_at->format('Y-m-d H:i:s'),
                "payment_date" => $action->date,
                "payment_value" => $action->amount,
                "call_result" => null,
                "phone" => null,
                "info" => null,
                "files" => null
            ];
        }

        return $res;
    }
}