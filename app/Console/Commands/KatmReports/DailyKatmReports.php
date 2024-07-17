<?php

namespace App\Console\Commands\KatmReports;

use App\Facades\KATM\RepKatm;
use App\Jobs\KatmReports\StackKatmReportByContractJob;
use App\Models\AccountingEntry;
use App\Models\Contract;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DailyKatmReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'katmReport:daily';

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

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $date = Carbon::now()->subDays(config('test.katm.delay_days'));
        $this->info('DAILY');
        $this->info('DATE: ' . $date->format('Y-m-d'));
        $contractsQuery = Contract::query()
            ->whereHas('accountingEntries', function ($query) use ($date) {
                $query->whereIn('destination_code', [
                    AccountingEntry::CODE_1007,
                    AccountingEntry::CODE_1008,
                    AccountingEntry::CODE_1009
                ])
                    ->whereRaw("DATE(operation_date) = '" . $date->format('Y-m-d') . "'");
            })
            // @todo Костыль блокирующий отправку проводок контрактов подтверждённых до апреля 2023 года
            ->whereRaw("DATE(confirmed_at) >= '2023-04-01'");
        $contracts = $contractsQuery->get();
        $contractsCount = $contractsQuery->count();
        $progressBar = $this->output->createProgressBar($contractsCount);
        $progressBar->start();
        foreach ($contracts as $contract) {
            $progressBar->advance();
            RepKatm::makeDailyContractReports($contract, $date);
            StackKatmReportByContractJob::dispatch($contract->id);
        }
        $progressBar->finish();
        $this->info("\n\rFINISH");
        return 0;
    }

}
