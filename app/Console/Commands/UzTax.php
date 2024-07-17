<?php

namespace App\Console\Commands;

use App\Http\Controllers\V3\UzTaxController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UzTax extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uztax:send';

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
        Log::channel('uz_tax')->info('created qr_code from payment,s data');
        (new UzTaxController)();

        return 0;
    }
}
