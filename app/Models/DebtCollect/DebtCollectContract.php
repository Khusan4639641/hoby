<?php

namespace App\Models\DebtCollect;

use App\Models\Contract;
use App\Models\ContractPaymentsSchedule;

class DebtCollectContract extends Contract
{
    protected $table = 'contracts';
    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['debt_sum'];

    public function debtor() {
        return $this->belongsTo(Debtor::class, 'user_id');
    }

    public function debts() {
        return $this->hasMany(ContractPaymentsSchedule::class, 'contract_id');
    }

    public function debt_collect_actions()
    {
        return $this->hasMany(DebtCollectContractAction::class, 'contract_id');
    }

    public function debt_collect_results() {
        return $this->hasMany(DebtCollectContractResult::class, 'contract_id');
    }

    public function actions_processed() {
        return $this->hasMany(DebtCollectContractProcessed::class, 'contract_id');
    }

    public function schedules()
    {
        return $this->hasMany(ContractPaymentsSchedule::class, 'contract_id');
    }

    public function getDebtSumAttribute() {
        return round($this->debts()->sum('balance')
            + $this->collcost()
                ->where('status', 0)
                ->sum('balance')
            + $this->autopay_history()
                ->where('status', 0)
                ->sum('balance')
        , 2);
    }
}
