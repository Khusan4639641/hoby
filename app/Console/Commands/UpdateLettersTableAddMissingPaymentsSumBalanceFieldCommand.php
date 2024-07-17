<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Letter;

class UpdateLettersTableAddMissingPaymentsSumBalanceFieldCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-letters:add-payments-sum-balance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Update `letters` table add missing payments_sum_balance field => (Одноразовая команда!!!)";

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

        $letters = Letter::whereNotNull("amounts")->where("created_at", "<", "2023-04-19 00:00:00")->count();

        $bar = $this->output->createProgressBar($letters);

        $bar->start();

        Letter::whereNotNull("amounts")
            ->where("created_at", "<", "2023-04-19 00:00:00")
            ->orderByDesc('id')
            ->chunk(100, function ($letters) use ($bar) {
                foreach ($letters as $letter) {
                    $amounts = $letter->amounts; // Laravel will convert JSON object into PHP array

                    if ( !array_key_exists("payments_sum_balance", $amounts) ) {  // Если нет поля $amounts["payments_sum_balance"]
                        $letter->amounts = [
                            "payments_sum_balance"        => $amounts["total_max_percent_fix_max"] - $amounts["fix_max"] - $amounts["percent"],
//                            "payments_sum_balance"        => $amounts["total_max_autopay_post_cost"] - $amounts["autopay"] - $amounts["post_cost"],
                            "autopay"                     => $amounts["autopay"],
                            "post_cost"                   => $amounts["post_cost"],
                            "total_max_autopay_post_cost" => $amounts["total_max_autopay_post_cost"],
                            "percent"                     => $amounts["percent"],
                            "fix_max"                     => $amounts["fix_max"],
                            "total_max_percent_fix_max"   => $amounts["total_max_percent_fix_max"]
                        ]; // Laravel converts PHP array into JSON object, because Letter Model has $casts attribute
                        $letter->save();
                    } else { Log::channel("letters")->error("`letters`.`id`: {$letter->id}, `letters`.`amounts` field 'payments_sum_balance' array_key_exists!"); }
                    $bar->advance();
                }
            })
        ;
        $bar->finish();
        return 'Ended';
    }
}
