<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAttemptsLimitReachedToScoringResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scoring_results', function (Blueprint $table) {
            $table->tinyInteger('attempts_limit_reached')->default(0);
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
            $table->dropColumn('attempts_limit_reached');
        });
    }
}
