<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInfoscoreStateColumnToScoringResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scoring_results', function (Blueprint $table) {
            $table->tinyInteger('overdue_by_infoscore_state')->nullable();
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
            $table->dropColumn('overdue_by_infoscore_state');
        });
    }
}
