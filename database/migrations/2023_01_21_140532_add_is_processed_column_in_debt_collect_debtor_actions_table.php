<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsProcessedColumnInDebtCollectDebtorActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('debt_collect_debtor_actions', function (Blueprint $table) {
            $table->timestamp('processed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('debt_collect_debtor_actions', function (Blueprint $table) {
            $table->dropColumn('processed_at');
        });
    }
}
