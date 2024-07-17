<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\MFOEventCloseContract;
use App\Models\MFOEventPayment;
use App\Services\MFO\AccountingEntryService;
use App\Services\MFO\AccountService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MFOContractClose extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mfo-contract:close';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close accounts and entries when contract closed';

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
        $this->alert('MFO CONTRACT CLOSE EVENTS');
        $items = MFOEventCloseContract::query()->where('status',MFOEventCloseContract::STATUS_NOT_PROCESSED)->get();
        $this->info('TOTAL: '. count($items));
        $success = 0;
        $fails = 0;
        if(count($items) > 0){
            $service = new AccountingEntryService();
            foreach ($items as $item) {
                $res = $service->closeAccountsAndEntries($item->contract_id, $item->contracts_close_date);
                if($res){
                    $item->status = MFOEventCloseContract::STATUS_PROCESSED;
                    $item->save();
                    $success++;
                }else{
                    $fails++;
                }
            }
        }
        $this->info('SUCCESS: '.$success.' FAILS: '.$fails);
        return 0;
    }
}
