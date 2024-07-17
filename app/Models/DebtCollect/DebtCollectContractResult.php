<?php

namespace App\Models\DebtCollect;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebtCollectContractResult extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'collector_id',
        'contract_id',
        'period_start_at',
        'rate',
        'period_end_at',
        'total_amount',
        'amount'
    ];

    public function contract() {
        return $this->belongsTo(DebtCollectContract::class, 'contract_id');
    }

    public function collector() {
        return $this->belongsTo(DebtCollector::class, 'collector_id');
    }

    public function payments() {
        $period_start_at = Carbon::parse($this->period_start_at);
        if($this->period_end_at) {
            $period_end_at = Carbon::parse($this->period_end_at);
        } else {
            $period_end_at = Carbon::now()->endOfMonth();
        }

        $actions_processed = $this->contract->actions_processed()
            ->where('collector_id', $this->collector_id)
            ->where('period_end_at', '>=', $period_start_at);
        if($period_end_at) {
            $actions_processed = $actions_processed->where('period_start_at', '<', $period_end_at)->get();
        }

        if(!count($actions_processed)) {
            return Payment::whereNull('id');
        }

        return Payment::where('contract_id', $this->contract_id)
            ->where('payment_system', '!=' , 'Autopay')
            ->whereIn('status', [1, -1])
            ->where(function($query) use($actions_processed, $period_start_at, $period_end_at) {
                foreach($actions_processed as $i => $action_processed) {
                    $process_period_start_at = $action_processed->period_start_at;
                    $process_period_end_at = Carbon::parse($action_processed->period_end_at)->endOfDay();

                    if($period_start_at->isAfter($process_period_start_at)) {
                        $process_period_start_at = $period_start_at;
                    }
                    if($period_end_at->isBefore($process_period_end_at)) {
                        $process_period_end_at = $period_end_at;
                    }
                    if($i === 0) {
                        $query->whereBetween('created_at', [$process_period_start_at, $process_period_end_at]);
                    } else {
                        $query->orWhereBetween('created_at', [$process_period_start_at, $process_period_end_at]);
                    }
                }
            });
    }
}
