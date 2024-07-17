<?php

namespace App\Aggregators\DebtCollectActionsAggregators;

use App\Helpers\FileHelper;
use App\Models\Contract;
use App\Models\DebtCollect\DebtCollectDebtorAction;
use App\Models\DebtCollect\DebtCollector;
use App\Models\DebtCollect\Debtor;
use App\Models\User;

class DebtCollectActionAggregator
{
    public function getByDebtor($debtor_id): array
    {
        return $this->aggregateData(Debtor::find($debtor_id)->debt_collect_actions()->with('files')->get());
    }

    public function getByPartnerContracts($contract_ids): array
    {
        return $this->aggregateData(DebtCollectDebtorAction::whereIn('debtor_id', Contract::whereIn('id', $contract_ids)->get()->pluck('user_id')->toArray())->with('files')->get());
    }

    public function getByContract($contract_id): array
    {
        return $this->aggregateData(DebtCollectDebtorAction::where('debtor_id', Contract::find($contract_id)->user_id)->with('files')->get());
    }

    private function aggregateData($data): array
    {
        $res = [];
        foreach ($data as $item) {
            $files = [];
            foreach ($item->files as $file) {
                $files = [
                    'url' => FileHelper::sourcePath() . $file->path
                ];
            }
            $res[] = [
                "user"          => DebtCollector::find($item->collect_agent_id)->fio,
                "contract_id"   => null,
                "type"          => $item->type,
                "content"       => $item->content,
                "created_at"    => $item->created_at->format('Y-m-d H:i:s'),
                "payment_date"  => null,
                "payment_value" => null,
                "call_result"   => null,
                "phone"         => null,
                "info"          => null,
                "files"         => $files
            ];
        }


        return $res;
    }
}