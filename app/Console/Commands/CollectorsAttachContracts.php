<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

use App\Models\Collector;
use App\Models\Contract;

class CollectorsAttachContracts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collectors:attachContracts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Привязка просроченных контрактов к коллекторам';

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
        $collectors = Collector::all();
        foreach($collectors as $collector) {
            $collector_contracts_ids = [];

            foreach($collector->katm_regions as $katm_region) {
                $region_contracts_ids = Contract::where('expired_days', '>=', 90)->whereHas('buyer', function(Builder $query) use($katm_region) {
                    $query->where('local_region', $katm_region->local_region);
                })->pluck('id');
                $collector_contracts_ids = array_merge($collector_contracts_ids, $region_contracts_ids->toArray());
            }

            $collector->contracts()->sync($collector_contracts_ids);
        }
    }
}
