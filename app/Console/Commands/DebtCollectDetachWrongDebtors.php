<?php

namespace App\Console\Commands;

use App\Models\DebtCollect\DebtCollectContractResult;
use App\Models\DebtCollect\DebtCollector;
use Illuminate\Console\Command;

class DebtCollectDetachWrongDebtors extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debtCollect:detachWrongDebtors';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Отвязка должников от коллектора, у которых не совпадают районы';

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
        $collectors = DebtCollector::with(['districts', 'debtors'])->get();
        foreach ($collectors as $ci => $collector) {
            $this->line("{$ci}. {$collector->id}: {$collector->full_name}");

            $collector_districts = $collector->districts;
            $this->comment(" Привязанные районы:");
            if(count($collector_districts) > 0) {
                foreach ($collector_districts as $di => $district) {
                    $this->line("  {$di}. {$district->id}: {$district->name}");
                }
            } else {
                $this->error("  Отсутствуют");
            }


            $debtors = $collector->debtors;
            $this->comment(" Привязанные должники:");
            if(count($debtors) > 0) {
                foreach ($debtors as $di => $debtor) {
                    $this->line("  {$di}. {$debtor->id}: {$debtor->full_name} ({$debtor->district->name})");
                    if(!in_array($debtor->district->id, $collector_districts->pluck('id')->toArray())) {
                        $attached_contracts = $collector->contracts()->where('user_id', $debtor->id)->pluck('contracts.id');
                        DebtCollectContractResult::whereIn('contract_id', $attached_contracts)->delete();
                        $collector->contracts()->detach($attached_contracts);
                        $collector->debtors()->detach($debtor->id);
                        $this->question("  - Отвязан успешно");
                    }
                }
            } else {
                $this->line("  Отсутствуют");
            }
            $this->line(' ');
        }
    }
}
