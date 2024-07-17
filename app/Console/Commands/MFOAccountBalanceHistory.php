<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Services\MFO\AccountBalanceHistoryService;
use App\Services\MFO\AccountingEntryService;
use Illuminate\Console\Command;

class MFOAccountBalanceHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'account-history:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create account balance history';

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
    public function handle() : int
    {
        $this->chargePercents();
        $this->createHistory();
        return 0;
    }

    private function chargePercents()
    {
        $this->alert('CHARGE PERCENTS');
        $service = new AccountingEntryService();
        $contracts = Contract::query()->whereIn('status',[1,3,4])->where('general_company_id','=',3)->get();
        $success = 0;
        $success_cbu = 0;
        $fails = 0;
        $fails_cbu = 0;
        $total = count($contracts);
        if($total > 0){
            foreach ($contracts as $key => $contract) {
                $result = $service->calculateAndChargePercent($contract);
                $result_cbu = $service->calculateAndChargePercent($contract,true);
                $result ? $success++ : $fails++;
                $result_cbu ? $success_cbu++ : $fails_cbu++;
                if($key%100 == 0){
                    $this->info('PROCESS GOING ON '.round(($key / $total) * 100).'%');
                }
            }
        }
        $this->info('TOTAL: '.$total.' SUCCESS: '.$success.' FAILS: '.$fails);
        $this->info('TOTAL (CBU): '.$total.' SUCCESS (CBU): '.$success_cbu.' FAILS (CBU): '.$fails_cbu);
    }

    private function createHistory()
    {
        $this->alert('BALANCE HISTORY');
        $service = new AccountBalanceHistoryService();
        //Account
        $result = $service->init(false);
        $this->info('TOTAL: '.$result['total'].' SUCCESS: '.$result['success'].' FAILS: '.$result['fails']);
        //AccountCBU
        $result_cbu = $service->init(true);
        $this->info('TOTAL: '.$result_cbu['total'].' SUCCESS: '.$result_cbu['success'].' FAILS: '.$result_cbu['fails']);
    }
}
