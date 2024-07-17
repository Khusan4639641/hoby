<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtCollectContractResultsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('debt_collect_contract_results', function (Blueprint $table) {
            $table->id();
            $table->integer('collector_id');
            $table->unsignedInteger('contract_id');
            $table->timestamp('period_start_at');
            $table->decimal('rate', 3, 2);
            $table->timestamp('period_end_at')->nullable();
            $table->decimal('total_amount', 16, 2)->nullable();
            $table->decimal('amount', 16, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('collector_id')->references('id')->on('users');
            $table->foreign('contract_id')->references('id')->on('contracts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('debt_collect_contract_results');
    }
}
