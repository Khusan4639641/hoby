<?php

use App\Models\Letter;
use App\Models\NotarySetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

class UpdateLettersTableAmountsJsonFieldAllAttributes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $post_cost = (float) NotarySetting::where("template_number", "fourth")->first()->fee; // Нам главное нужно чтобы тут было 15 000 сум (константа):
        $fix_max   = (float) NotarySetting::max("fee");

        Letter::with([
            "contract" => function ($query) {
                return $query->with([
                    "activePayments" => function ($query) { // 'autopay_debit_history' table
                        $query->select("id", "contract_id", "balance");
                    },
                ])
                    ->select("id");
            },
        ])
            ->orderByDesc("id")
            ->chunk(100, function ($letters) use ($post_cost, $fix_max) {
                foreach ($letters as $letter) {
                    $contract = $letter->contract;
                    if ( $contract ) {
                        $payments_sum_balance = 0;
                        if ($contract->activePayments) {
                            $payments_sum_balance = (float) $contract->activePayments->sum("balance");
                        } else {
                            Log::channel("letters")->error("Letters migration error! Contract: " .
                                $contract->id . ", ContractPaymentsSchedules are absent!"
                            );
                        }
                        $autopay = ($payments_sum_balance * 100)/97 - $payments_sum_balance;
                        $percent = (float) ($payments_sum_balance / 100);

                        $letter->amounts = [
                            "total_max_autopay_post_cost" => ($payments_sum_balance + $autopay + $post_cost), // Путь нотариуса
                            "autopay" => $autopay,
                            "post_cost" => $post_cost,
                            "total_max_percent_fix_max" => ($payments_sum_balance + $percent + $fix_max), // Путь суда
                            "percent" => $percent,
                            "fix_max" => $fix_max,
                        ];
                        $letter->save();
                    } else {
                        Log::channel("letters")->error("Letters migration error! Contract: " .
                            $contract->id . ", doesn't exist!"
                        );
                    }
                }
            })
        ;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
