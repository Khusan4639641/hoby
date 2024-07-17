<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

use App\Models\DebtCollect\DebtCollectContractResult;
use Illuminate\Database\Eloquent\Builder;

class DebtCollectNewRate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debtCollect:newRate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Исправляет 5% на 7% в debt_collect_contract_results за февраль 2023';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        DebtCollectContractResult::whereHas('contract', function(Builder $query) {
            $query->where('expired_days', '>=', 300);
        })->where('period_start_at', '>=', Carbon::now()->startOfMonth())
            ->update(['rate' => 7]);
    }
}
