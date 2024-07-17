<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToAccounts1CTempTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounts_1c_temp', function (Blueprint $table) {
            $table->bigInteger('last_debit_account_sum')->default(0);
            $table->bigInteger('last_credit_account_sum')->default(0);
            $table->boolean('is_from_1c_api')->default(true);
        });

        DB::statement('ALTER TABLE accounts_1c_temp MODIFY COLUMN contract_bind TINYINT(1) DEFAULT 0 AFTER last_credit_account_sum');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounts_1c_temp', function (Blueprint $table) {
            $table->dropColumn(['last_debit_account_sum', 'credit_account_sum', 'is_from_1c_api']);
        });
    }
}
