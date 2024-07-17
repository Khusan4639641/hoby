<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractRoyxatExportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contract_royxat_exports', function (Blueprint $table) {
            $table->id();
            $table->integer('contract_id')->unsigned()->unique();
            $table->foreign('contract_id')->references('id')->on('contracts');
            $table->tinyInteger('status')->unsigned();
            $table->smallInteger('expired_days')->unsigned();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('royxat_debtors');
    }
}
