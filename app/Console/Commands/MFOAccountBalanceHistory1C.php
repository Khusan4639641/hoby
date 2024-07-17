<?php

namespace App\Console\Commands;

use App\Services\MFO\Account1CService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class MFOAccountBalanceHistory1C extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mfo-account:update-balance-history-1c {start?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update 1C accounts balance history with data from 1C API for the given period of time. If no period is given, the current day will be updated.
        {start : The start date of the period to update (including) (optional).}';

    /**
     * Execute the console command.
     *
     * @return boolean
     */
    public function handle(Account1CService $service): bool
    {
        $service->calculateBalance($this->getStartDate());

        $this->info('MFO accounts balance history updated successfully. Check the logs for more details.');
        return true;
    }

    private function getStartDate(): Carbon
    {
        if ($this->hasArgument('start')) {
            return Carbon::parse($this->argument('start'));
        }

        return Carbon::now();
    }
}
