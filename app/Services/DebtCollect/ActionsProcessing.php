<?php

namespace App\Services\DebtCollect;

use App\Models\DebtCollect\Debtor;
use App\Models\DebtCollect\DebtCollectContractProcessed;
use App\Models\DebtCollect\DebtCollector;
use App\Scopes\DebtCollectScope;
use Carbon\Carbon;

class ActionsProcessing
{
    private const MAIN_ACTIONS_TYPES = ['location', 'photo', 'date'];
    private const MINIMAL_MAIN_ACTIONS_COUNT = 2;
    private const MAXIMUM_ACTIONS_DELAY_DAYS = 5;

    public static function sync($collector_id, $debtor_id) {
        $collector = DebtCollector::withoutGlobalScope(DebtCollectScope::class)
            ->with(['contracts' => function($query) use($debtor_id) {
                $query->withTrashedPivots()->where('user_id', $debtor_id);
            }, 'debtor_actions' => function($query) use($debtor_id) {
                $query->where('debtor_id', $debtor_id)->whereNull('processed_at');
            }])
            ->find($collector_id);
        if(!$collector) {
            return;
        }

        $debtor = Debtor::with(['contracts', 'debt_collect_actions' => function($query) use($collector_id) {
            $query->where('collect_agent_id', $collector_id);
        }])->find($debtor_id);
        if(!$debtor) {
            return;
        }

        $main_actions_types = collect(static::MAIN_ACTIONS_TYPES);
        $possible_actions_types = collect(static::MAIN_ACTIONS_TYPES);
        $processing_actions = [];

        $debtor_actions = $collector->debtor_actions;
        if(!$debtor_actions) {
            return;
        }
        foreach ($debtor_actions as $action) {
            $minimal_action_date = Carbon::parse($action->created_at)->sub('days', static::MAXIMUM_ACTIONS_DELAY_DAYS);
            foreach ($processing_actions as $i => $processing_action) {
                if(Carbon::parse($processing_action->created_at)->isBefore($minimal_action_date)) {
                    if($main_actions_types->get($processing_action->type)) {
                        if(!$possible_actions_types->get($processing_action->type)) {
                            $possible_actions_types->push($processing_action->type);
                        }
                    }
                    unset($processing_actions[$i]);
                }
            }

            $processing_actions[] = $action;

            $possible_actions_types = $possible_actions_types->filter(function ($type) use($action) {
                return $type !== $action->type;
            });

            $current_main_actions_count = count($main_actions_types) - count($possible_actions_types);
            if($current_main_actions_count < static::MINIMAL_MAIN_ACTIONS_COUNT) {
                continue;
            }

            foreach ($collector->contracts as $contract) {
                $period_end_at = Carbon::parse($action->created_at)->add('month', 1);
                DebtCollectContractProcessed::create([
                    'collector_id' => $collector->id,
                    'contract_id' => $contract->id,
                    'period_start_at' => $action->created_at,
                    'period_end_at' => $period_end_at
                ]);
            }

            foreach ($processing_actions as $processing_action) {
                $processing_action->processed_at = $action->created_at;
                $processing_action->save();
            }

            $possible_actions_types = clone $main_actions_types;
            $processing_actions = [];
        }
    }
}