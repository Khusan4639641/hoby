<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAccountEntryIdToContractPaymentsSchedule extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contract_payments_schedule', function (Blueprint $table) {
            $table->unsignedBigInteger('account_entry_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contract_payments_schedule', function (Blueprint $table) {
            $table->dropColumn(['account_entry_id']);
        });
    }
}
