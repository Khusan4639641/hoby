<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\Contract;
use App\Services\MFO\AccountingEntryService;
use Illuminate\Console\Command;

class MFOAccountEntryReserve extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'account-entry:reserve';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Процедуры выход платежа в просрочку и создания резервов';

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
        $this->paymentDelay();
        $this->reserve();
        return 0;
    }

    //выход платежа в просрочку
    private function paymentDelay()
    {
        $this->alert('MFO payment delay start');
        $success = 0;
        $fails = 0;
        $contracts = Contract::query()
                    ->whereHas('debts',function ($query) {
                        $query->where('balance','>',0)->whereNull('account_entry_id');
                    })
                    ->where('general_company_id','=',3)
                    ->whereIn('status',[Contract::STATUS_OVERDUE_30_DAYS,Contract::STATUS_OVERDUE_60_DAYS])
                    ->get();
        if($contracts->count() > 0){
            $service = new AccountingEntryService();
            foreach ($contracts as $contract){
                foreach ($contract->debts as $debt) {
                    if($debt->account_entry_id == null){
                        $res = $service->createEntryWithDelay($debt,now());
                        $res ? $success++ : $fails++;
                    }
                }
            }
            $this->info('SUCCESS: '.$success.' FAILS: '.$fails);
        }
    }

    //создания резервов
    private function reserve()
    {
        $this->alert('MFO reserve start');
        $success = 0;
        $fails = 0;
        $contracts = Contract::query()->whereIn('expired_days',[31,61,91])->get();
        $this->info('TOTAL: '.count($contracts));
        if(count($contracts) > 0){
            foreach ($contracts as $contract) {
                $service = new AccountingEntryService();
                $res = $service->createEntryReserve($contract->id,now());
                $res ? $success++ : $fails++;
            }
        }
        $this->info('SUCCESS: '.$success.' FAILS: '.$fails);
    }
}
