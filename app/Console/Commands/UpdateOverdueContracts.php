<?php

namespace App\Console\Commands;

use App\Facades\BuyerDebtor;
use Illuminate\Console\Command;

class UpdateOverdueContracts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'contracts:update-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update contracts expired days and overdue statuses';

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
        BuyerDebtor::updateContractsExpiredDays();
        BuyerDebtor::updateClosedPaymentsContractsStatus();
        BuyerDebtor::updatePartialOverdueContractsStatus();
        BuyerDebtor::updateFullOverdueContractsStatus();
        BuyerDebtor::addOverdueToBlackList();
        return 0;
    }
}
