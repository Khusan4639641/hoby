<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtCollectorContractTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('debt_collector_contract', function (Blueprint $table) {
            $table->id();
            $table->integer('collector_id');
            $table->unsignedInteger('contract_id');
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
        Schema::dropIfExists('debt_collector_contract');
    }
}
