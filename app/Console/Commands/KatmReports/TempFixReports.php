<?php

namespace App\Console\Commands\KatmReports;

use App\Facades\KATM\RepKatm;
use App\Jobs\KatmReports\StackKatmReportByContractJob;
use App\Models\AccountingEntry;
use App\Models\Contract;
use App\Models\KatmReport;
use Illuminate\Console\Command;

class TempFixReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'katmReport:fix';

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
        $this->fix001ReportLongAddress();
        return 0;
    }


    private function fix001ReportLongAddress()
    {
        $limitDate = '2023-05-31';
        $this->info("001 (FIX LONG ADDRESS)");
        $contractsQuery = KatmReport::query()
            ->selectRaw('contract_id AS id')
            ->where('report_number', KatmReport::NUMBER_LOAN_REG)
            ->where('status', '!=', KatmReport::STATUS_COMPLETE)
            ->whereRaw("katm_reports.error_response LIKE '%05955%'")
            ->whereRaw("DATE(katm_reports.created_at) <= '$limitDate'")
            ->whereHas('contract', function ($query) {
                $query->whereIn('status', [
                    Contract::STATUS_ACTIVE,
                    Contract::STATUS_OVERDUE_60_DAYS,
                    Contract::STATUS_OVERDUE_30_DAYS,
                ])
                    ->orWhere(function ($query) {
                        $query->where('status', Contract::STATUS_COMPLETED)
                            ->whereRaw('cancel_reason IS NULL');
                    });
            });
        $contractsList = $contractsQuery->get();
        $progressBar = $this->output->createProgressBar($contractsQuery->count());
        $progressBar->start();
        foreach ($contractsList as $contractItem) {
            $progressBar->advance();

            $contract = Contract::find($contractItem['id']);
            $contract->katmReport()
                ->where('status', '!=', KatmReport::STATUS_COMPLETE)
                ->delete();
            $contractAccountingEntriesDates = $contract->accountingEntries()
                ->where('status', AccountingEntry::STATUS_ACTIVE)
                ->whereIn('destination_code', [
                    AccountingEntry::CODE_1007,
                    AccountingEntry::CODE_1008,
                    AccountingEntry::CODE_1009
                ])
                ->whereRaw("DATE(created_at) <= '$limitDate'")
                ->groupByRaw('DATE(operation_date)')
                ->selectRaw('DATE(operation_date) AS date')
                ->get();
            foreach ($contractAccountingEntriesDates as $date) {
                RepKatm::makeDailyContractReports($contract, $date['date']);
            }
            StackKatmReportByContractJob::dispatch($contract->id);
        }
        $progressBar->finish();
        $this->info("\n\rFINISH 001");
    }


}
