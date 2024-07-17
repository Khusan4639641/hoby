<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AccountCBU;
use App\Models\AccountingEntry;
use App\Models\AccountingEntryCBU;
use App\Models\Contract;
use App\Services\MFO\MFOPaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MFOAccountEntriesCreater extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'account-entries:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates accounts and entries when contract activated';

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
        $this->alert('ACCOUNT & ENTRIES INIT');
        $contracts = Contract::query()
                            ->with('order')
                            ->whereIn('status',[1,3,4,9])
                            ->where('general_company_id','=',3)
                            ->whereNull('cancel_reason')
                            ->get();
        $success = 0;
        $fails = 0;
        $totals = count($contracts);
        if($totals > 0){
            $service = new MFOPaymentService();
            foreach ($contracts as  $key => $contract) {
                try {
                    if(!$this->isInitialized($contract->id)){
                        $service->init($contract);
                    }
                    $success++;
                    if($key%100 == 0){
                        $this->info('PROCESS GOING ON '.round(($key / $totals) * 100).'%');
                    }
                }catch (\Exception $exception){
                    Log::channel('mfo_account_errors')->info('AccountEntries INIT_ERROR: '.$exception->getMessage());
                    $fails ++;
                }
            }
        }
        $this->info('TOTAL: '.$totals.' SUCCESS: '.$success.' FAILS: '.$fails);
        return 0;
    }

    private function isInitialized(int $contract_id) : bool
    {
        $accounts = Account::query()->where('contract_id','=',$contract_id)->where('status','=',Account::STATUS_OPEN)->count();
        $accounts_cbu = AccountCBU::query()->where('contract_id','=',$contract_id)->where('status','=',Account::STATUS_OPEN)->count();
        $entries = AccountingEntry::query()->where('contract_id','=',$contract_id)->where('status','=',AccountingEntry::STATUS_ACTIVE)->count();
        $entries_cbu = AccountingEntryCBU::query()->where('contract_id','=',$contract_id)->where('status','=',AccountingEntry::STATUS_ACTIVE)->count();
        if(!$accounts && !$accounts_cbu && !$entries && !$entries_cbu){
            return false;
        }
        return true;
    }
}
