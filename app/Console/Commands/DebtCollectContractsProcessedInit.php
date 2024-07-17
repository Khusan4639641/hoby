<?php

namespace App\Console\Commands;

use App\Models\DebtCollect\DebtCollector;
use App\Scopes\DebtCollectScope;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Services\DebtCollect\ActionsProcessing;

use App\Models\DebtCollect\DebtCollectContractProcessed;

class DebtCollectContractsProcessedInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debtCollectContractsProcessed:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Разовая команда! Создаёт записи в processed задним числом.';

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
        $this->info("Загрузка коллекторов...");
        $collectors = DebtCollector::withoutGlobalScope(DebtCollectScope::class)
            ->with(['debtorsWithTrashed'])
            ->get(['id']);
        $this->info("- Успех!");

        $this->info("Проходимся по коллекторам...");
        foreach ($collectors as $collector) {
            $this->info("- Коллектор №{$collector->id}");
            foreach ($collector->debtorsWithTrashed as $debtor) {
                $this->info("-- Должник №{$debtor->id}");
                ActionsProcessing::sync($collector->id, $debtor->id);
                $this->info("--- Успех");
            }
        }
    }
}
