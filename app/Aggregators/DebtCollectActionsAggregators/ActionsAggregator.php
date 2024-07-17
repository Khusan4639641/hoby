<?php

namespace App\Aggregators\DebtCollectActionsAggregators;

class ActionsAggregator
{
    public static function getByDebtors($debtor_id): array
    {
        return array_merge((new DeltaActionsAggregator())->getByDebtor($debtor_id),
            (new DebtCollectActionAggregator())->getByDebtor($debtor_id),
            (new CollectorActionsAggregator())->getByDebtor($debtor_id),
            (new PartnerActionsAggregator())->getByDebtor($debtor_id));
    }

    public static function getByPartnerContracts($contract_ids): array
    {
        return array_merge((new DeltaActionsAggregator())->getByPartnerContracts($contract_ids),
            (new DebtCollectActionAggregator())->getByPartnerContracts($contract_ids),
            (new CollectorActionsAggregator())->getByPartnerContracts($contract_ids),
            (new PartnerActionsAggregator())->getByPartnerContracts($contract_ids));
    }

    public static function getForDeltaByContract($contract_id): array
    {
        return array_merge((new DeltaActionsAggregator())->getByContract($contract_id),
            (new DebtCollectActionAggregator())->getByContract($contract_id),
            (new CollectorActionsAggregator())->getByContract($contract_id),
            (new PartnerActionsAggregator())->getByContract($contract_id));
    }
}