<?php

namespace App\Console\Commands\KatmReports;

use App\Facades\KATM\RepKatm;
use App\Models\AccountingEntry;
use App\Models\Contract;
use App\Models\GeneralCompany;
use App\Models\KatmReport;
use Illuminate\Console\Command;

class ChunkKatmReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'katmReport:chunk {from} {to} {--need=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send KATM reports by chunk';

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

        $this->info('CHUNK');

        $need = $this->option('need');

        $from = (int)$this->argument('from');
        $to = (int)$this->argument('to');

        if ($from === 0) {
            $this->error("Param 'from' must be higher than 0");
        }

        if ($to === 0) {
            $this->error("Param 'to' must be higher than 0");
        }

        switch ($need) {
            case "generate":
                $this->generate($from, $to);
                break;
            case "send":
                $this->send($from, $to);
                break;
            default:
                $this->error("Param 'need' is required");
                break;
        }

        return 0;
    }

    private function generate(int $from, int $to): void
    {
        $this->info('GENERATE');

        $this->info("INIT");

        $contractsQuery = Contract::query()
            ->where(function ($query) {
                $query->whereIn('status', [
                    Contract::STATUS_ACTIVE,
                    Contract::STATUS_OVERDUE_60_DAYS,
                    Contract::STATUS_OVERDUE_30_DAYS,
                ])
                    ->orWhere(function ($query) {
                        $query->where('status', Contract::STATUS_COMPLETED)
                            ->whereRaw('cancel_reason IS NULL');
                    });
            })
            ->whereHas('generalCompany', function ($query) {
                $query->where('type', GeneralCompany::TYPE_MFI);
            })
            ->whereHas('katmReport', function ($query) {
                $query->where('report_number', KatmReport::NUMBER_START_STATUS)
                    ->where('status', KatmReport::STATUS_COMPLETE);
            })
            ->whereDoesntHave('katmReport', function ($query) {
                $query->where('report_number', KatmReport::NUMBER_LOAN_AGREEMENT);
            })
            ->where('id', '>=', $from)
            ->where('id', '<=', $to);

        $contractsCount = $contractsQuery->count();
        $this->info("CONTRACTS COUNT: " . $contractsCount);
        $contracts = $contractsQuery->get();

        $progressBar = $this->output->createProgressBar($contractsCount);
        $progressBar->start();
        foreach ($contracts as $contract) {
            $progressBar->advance();
            $this->generateByContract($contract);
        }
        $progressBar->finish();
        $this->info("\n\rFINISH");
    }

    private function generateByContract(Contract $contract): void
    {
        $contractAccountingEntriesDates = $contract->accountingEntries()
            ->whereIn('destination_code', [
                AccountingEntry::CODE_1007,
                AccountingEntry::CODE_1008,
                AccountingEntry::CODE_1009
            ])
            ->groupByRaw('DATE(operation_date)')
            ->selectRaw('DATE(operation_date) AS date')
            ->get();
        foreach ($contractAccountingEntriesDates as $date) {
//            RepKatm::processSingle($contract, $date['date']);
        }
    }

    private function send(int $from, int $to): void
    {
        $this->info('SEND');
    }

}
