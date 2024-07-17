<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTemporaryHotfixOnCollectorTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('collector_transactions', function (Blueprint $table) {
            $table->dropForeign('collector_transactions_collector_contract_id_foreign');
            $table->foreign('collector_contract_id')
                  ->references('id')
                  ->on('collector_contract')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('collector_transactions', function (Blueprint $table) {
            $table->dropForeign('collector_transactions_collector_contract_id_foreign');
            $table->foreign('collector_contract_id')
                  ->references('id')
                  ->on('collector_contract');
        });
    }
}
