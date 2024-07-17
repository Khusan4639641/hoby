<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\MFOEventPayment;
use App\Services\MFO\AccountingEntryService;
use App\Services\MFO\AccountService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MFOPayment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mfo-payment:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create MFO entries';

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
        $this->alert('MFO PAYMENT EVENTS');
        $payments = MFOEventPayment::query()->where('status',MFOEventPayment::STATUS_NOT_PROCESSED)->get();
        $this->info('TOTAL: '. count($payments));
        $success = 0;
        $fails = 0;
        if(count($payments) > 0){
            $service = new AccountingEntryService();
            foreach ($payments as $payment) {
                try {
                    $account_12405 = AccountService::getAccountModel(null,$payment->contract_id,'12405')->first();
                    if($account_12405){
                        if($account_12405->getBalance() > 0){
                            $service->createEntryWithDebt($payment->contract_id,$payment->amount,$payment->record_created_at,$payment->id);
                        }else{
                            $service->createEntryWithoutDebt($payment->contract_id,$payment->amount,$payment->record_created_at,$payment->id);
                        }
                        $payment->status = MFOEventPayment::STATUS_PROCESSED;
                        $payment->save();
                        $success++;
                    }
                    else{
                        $fails++;
                        Log::channel('mfo_account_errors')->info('MFO_PAYMENT_EVENT: Account with mask 12405 does not exist where contract_id = '.$payment->contract_id);
                    }
                }
                catch (\Exception $exception){
                    Log::channel('mfo_account_errors')->info('MFO_PAYMENT_EVENT: '.$exception->getMessage());
                    $fails++;
                }
            }
        }
        $this->info('SUCCESS: '.$success.' FAILS: '.$fails);
        return 0;
    }
}
