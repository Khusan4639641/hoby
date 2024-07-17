<?php

use App\Models\Letter;
use App\Models\NotarySetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class InsertIntoLettersTableAmountsField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $post_cost = (float) NotarySetting::where("template_number", "fourth")->first()->fee; // Нам главное нужно чтобы тут было 15 000 сум (константа):
        $fix_max   = (float) NotarySetting::max('fee');
        Letter::with([
            "contract" => function ($query) {
                return $query->with([
                    'generalCompany' => function ($query) { // 'general_companies' table
                        $query->select('id', 'is_tpp');
                    },
                    'activePayments' => function ($query) { // 'autopay_debit_history' table
                        $query->select('id', 'contract_id', 'balance');
                    },
                ])
                    ->select('id', 'general_company_id', 'total');
            },
        ])->whereAmounts(NULL) // letters.amounts (it is a JSON field), and for old records it is NULL
            ->orderByDesc('id')
            ->chunk(100, function ($letters) use ($post_cost, $fix_max) {

                foreach ($letters as $letter) {
                    $contract = $letter->contract;
                    if ( $contract ) {
                        if ($contract->activePayments) {
                            $payments_sum_balance = $contract->activePayments->sum('balance');

                            if ($contract->generalCompany) {
                                if ($contract->generalCompany->is_tpp) {
                                    // Путь суда
                                    $autopay = ($payments_sum_balance * 100)/97 - $payments_sum_balance;

                                    $amounts['total_max_amount'] = ($payments_sum_balance + $autopay + $post_cost);
                                    $amounts['autopay'] = $autopay;
                                    $amounts['post_cost'] = $post_cost;
                                    $amounts['percent'] = null;
                                    $amounts['fix_max'] = null;
                                } else {
                                    // Путь нотариуса
                                    $percent = (float) ($contract->total / 100);

                                    $amounts['total_max_amount'] = ($payments_sum_balance + $percent + $fix_max);
                                    $amounts['autopay'] = null;
                                    $amounts['post_cost'] = null;
                                    $amounts['percent'] = $percent;
                                    $amounts['fix_max'] = $fix_max;
                                }

                                $letter->amounts = $amounts; // Laravel converts array => JSON, because Letter Model has $casts attribute
                                $letter->save();
                            } else {
                                Log::channel("letters")->error("Letters migration error! Contract: " .
                                    $contract->id . ", generalCompany is absent!"
                                );
                            }
                        } else {
                            Log::channel("letters")->error("Letters migration error! Contract: " .
                                $contract->id . ", ContractPaymentsSchedules are absent!"
                            );
                        }
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
//        Letter::whereNotNull('amounts')
//            ->orderByDesc('id')
//            ->chunk(100, function ($letters) {
//                foreach ($letters as $letter) {
//                    $letter->amounts = NULL;
//                    $letter->save();
//                    if ( !$letter->wasChanged('amounts') ) {
//                        Log::channel("letters")->error("Letters migration error!"
//                            . "Wasn't able to make the amounts field a NULL for letter with ID: " . $letter->id . "."
//                        );
//                    }
//                }
//            })
//        ;
    }
}
