<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInitiatorToCoringResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scoring_results', function (Blueprint $table) {
            $table->integer('initiator_id')->index()->nullable();
        });

        Schema::table('scoring_results', function (Blueprint $table) {
            $table->foreign('initiator_id')
                ->references('id')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('scoring_results', function (Blueprint $table) {
            $table->dropForeign('scoring_results_initiator_id_foreign');
        });
        Schema::table('scoring_results', function (Blueprint $table) {
            $table->dropColumn('initiator_id');
        });
    }
}
