<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtCollectorDebtorTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('debt_collector_debtor', function (Blueprint $table) {
            $table->id();
            $table->integer('collector_id');
            $table->integer('debtor_id');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('collector_id')->references('id')->on('users');
            $table->foreign('debtor_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('debt_collector_debtor');
    }
}
