<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMFOPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mfo_payments', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('type',50);
            $table->tinyInteger('status')->default(1);
            $table->integer('contract_id')->unsigned();
            $table->decimal('amount',16,2);
            $table->timestamps();
        });

        Schema::table('mfo_payments', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
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
        Schema::dropIfExists('mfo_payments');
    }
}
