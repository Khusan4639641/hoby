<?php

namespace App\Console\Commands;

use App\Models\MissingUzTax;
use App\Traits\UzTaxTrait;
use App\Models\UzTax;
use Illuminate\Console\Command;

class SendMissingChecks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uztax:send-missing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends missing checks';

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
        $missingChecks = MissingUzTax::all();
        foreach ($missingChecks as $missingCheck) {
            $uzTaxes = UzTax::where([
                'status' => UzTax::ACCEPT,
                'type' => UzTax::RECEIPT_TYPE_SELL,
                'contract_id' => $missingCheck->contract_id,
            ])->orderByDesc('id')->get();

            if ($uzTaxes->count() >= $missingCheck->quantity) {
                for ($i = 0; $i < $missingCheck->quantity; $i++) {
                    UzTaxTrait::refundReturnProduct($missingCheck->contract->id, $uzTaxes[$i]->id);
                }
                MissingUzTax::where('id', $missingCheck->id)->delete();
            }
        }
        return self::SUCCESS;
    }
}
