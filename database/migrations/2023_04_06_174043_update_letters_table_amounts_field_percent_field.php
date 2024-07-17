<?php

use App\Models\Letter;
use App\Models\NotarySetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class UpdateLettersTableAmountsFieldPercentField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
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
                    ->select('id', 'general_company_id');
            },
        ]) // letters.amounts (it is a JSON field), and for old records it is NULL
            ->orderByDesc('id')
            ->chunk(100, function ($letters) use ($fix_max) {
                foreach ($letters as $letter) {
                    $contract = $letter->contract;
                    if ( $contract ) {
                        if ($contract->activePayments) {
                            $payments_sum_balance = $contract->activePayments->sum('balance');

                            if ($contract->generalCompany) {
                                if ( !($contract->generalCompany->is_tpp) ) {  // Путь нотариуса
                                    $percent = (float) ($payments_sum_balance / 100);
                                    if ( !empty($letter->amounts) ) {
                                        $amounts = $letter->amounts;
                                        $amounts['percent'] = $percent;
                                        if ( isset($amounts['fix_max']) && !empty($amounts['fix_max']) ) {
                                            $amounts['total_max_amount'] = ($payments_sum_balance + $percent + $amounts['fix_max']);
                                        } else {
                                            Log::channel("letters")->error("Letters migration error! Contract: " .
                                                $contract->id . ", amounts JSON attribute \"fix_max\" field is empty!"
                                            );
                                            $amounts['total_max_amount'] = ($payments_sum_balance + $percent + $fix_max);
                                        }
                                        $letter->amounts = $amounts; // Laravel converts array => JSON, because Letter Model has $casts attribute
                                        $letter->save();
                                    } else {
                                        Log::channel("letters")->error("Letters migration error! Contract: " .
                                            $contract->id . ", amounts JSON field is empty!"
                                        );
                                    }
                                }
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
        //
    }
}
