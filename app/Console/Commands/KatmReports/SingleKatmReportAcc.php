<?php

namespace App\Console\Commands\KatmReports;

use App\Facades\KATM\RepKatm;
use App\Jobs\KatmReports\StackKatmReportByContractJob;
use App\Models\AccountingEntry;
use App\Models\Contract;
use App\Models\KatmReport;
use Illuminate\Console\Command;

class SingleKatmReportAcc extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'katmReport:single {contractID} {--need=}';

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

        $this->info('SINGLE');

        $contractID = $this->argument('contractID');
        $this->info("START");

        $this->info("FIND: " . $contractID);
        $contract = Contract::find($contractID);

        if (!$contract) {
            $this->error($contractID . " - contract not found");
            return 0;
        }

        $need = $this->option('need');

        switch ($need) {
            case "make":
                $this->makeReports($contract);
                break;
            case "send":
                $this->sendReports($contract);
                break;
            case "retry":
                $this->retryToMakeAndSendReports($contract);
                break;
            default:
                $this->makeAndSendReports($contract);
                break;
        }

        $this->info("FINISH");
        return 0;
    }

    private function makeAndSendReports(Contract $contract): void
    {
        $contractAccountingEntriesDates = $contract->accountingEntries()
            ->where('status', AccountingEntry::STATUS_ACTIVE)
            ->whereIn('destination_code', [
                AccountingEntry::CODE_1007,
                AccountingEntry::CODE_1008,
                AccountingEntry::CODE_1009
            ])
            ->groupByRaw('DATE(operation_date)')
            ->selectRaw('DATE(operation_date) AS date')
            ->get();
        $progressBar = $this->output->createProgressBar($contractAccountingEntriesDates->count());
        $progressBar->start();
        foreach ($contractAccountingEntriesDates as $date) {
            $progressBar->advance();
            RepKatm::makeDailyContractReports($contract, $date['date']);
        }
        $progressBar->finish();
        $this->info("\n\rSEND");
        StackKatmReportByContractJob::dispatch($contract->id);
    }

//    @todo Метод должен только формировать отчёты
    private function makeReports(Contract $contract): void
    {
        $contractAccountingEntriesDates = $contract->accountingEntries()
            ->where('status', AccountingEntry::STATUS_ACTIVE)
            ->whereIn('destination_code', [
                AccountingEntry::CODE_1007,
                AccountingEntry::CODE_1008,
                AccountingEntry::CODE_1009
            ])
            ->groupByRaw('DATE(operation_date)')
            ->selectRaw('DATE(operation_date) AS date')
            ->get();
        $progressBar = $this->output->createProgressBar($contractAccountingEntriesDates->count());
        $progressBar->start();
        foreach ($contractAccountingEntriesDates as $date) {
            $progressBar->advance();
//            @todo Метод должен только формировать отчёты
            RepKatm::makeDailyContractReports($contract, $date['date']);
        }
        $progressBar->finish();
        $this->info("\n\rSEND");
    }

    private function sendReports(Contract $contract): void
    {
        StackKatmReportByContractJob::dispatch($contract->id);
    }

    private function retryToMakeAndSendReports(Contract $contract): void
    {
        $contract->katmReport()
            ->where('status', '!=', KatmReport::STATUS_COMPLETE)
            ->delete();
        $this->makeAndSendReports($contract);
    }

}
