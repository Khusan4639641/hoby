<?php

namespace App\Console\Commands;

use App\Models\DebtCollect\DebtCollectContractResult;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DebtCollectCloseDeletedContractResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debtCollect:closeDeletedContractResults';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        DB::transaction(function () {
            $wrong_contract_results = DebtCollectContractResult::withTrashed()
                ->whereNotNull('deleted_at')
                ->whereNull('period_end_at')->get();
            foreach ($wrong_contract_results as $contract_result) {
                $deleted_date = Carbon::parse($contract_result->deleted_at);
                $period_end_date = Carbon::parse($contract_result->period_start_at)->endOfMonth();
                if($period_end_date->isSameMonth($deleted_date)){
                    $contract_result->period_end_at = $deleted_date;
                } else {
                    $contract_result->period_end_at = $period_end_date;
                }

                $contract_result->save();
            }
        });
    }
}
