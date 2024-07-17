<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScoringResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scoring_results', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->index();
            $table->tinyInteger('scoring_state')->nullable();
            $table->tinyInteger('scoring_by_tax_state')->nullable();
            $table->tinyInteger('debts_by_pinfl_state')->nullable();
            $table->tinyInteger('debts_by_royxat_state')->nullable();
            $table->tinyInteger('debts_by_mib_state')->nullable();
            $table->tinyInteger('debts_by_katm_state')->nullable();
            $table->tinyInteger('total_state')->nullable();
            $table->decimal('scoring_limit', 16, 2)->nullable();
            $table->decimal('scoring_by_tax_limit', 16, 2)->nullable();
            $table->decimal('final_limit', 16, 2)->nullable();
            $table->string('katm_claim', 40)->nullable();
            $table->string('katm_token', 40)->nullable();
            $table->tinyInteger('is_katm_auto')->nullable();
            $table->tinyInteger('banned_state')->nullable();
            $table->tinyInteger('final_state')->nullable();
            $table->tinyInteger('write_off_check_state')->nullable();
            $table->tinyInteger('tries_count')->default(0);
            $table->timestamps();
        });

        Schema::table('scoring_results', function (Blueprint $table) {
            $table->foreign('user_id')
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
            $table->dropForeign('scoring_results_user_id_foreign');
        });
        Schema::dropIfExists('scoring_results');
    }
}
