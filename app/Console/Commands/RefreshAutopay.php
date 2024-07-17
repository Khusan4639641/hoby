<?php

namespace App\Console\Commands;

use App\Facades\UniversalAutoPayment;
use Illuminate\Console\Command;

class RefreshAutopay extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'autopay:refresh {--debtors}';

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

        $isDebtors = $this->option('debtors');

        if ($isDebtors) {
//            UniversalAutoPayment::registerUniversalDebtors();
        } else {
            UniversalAutoPayment::getPayments();
        }


        $this->line('Completed!');

        return 0;
    }
}
