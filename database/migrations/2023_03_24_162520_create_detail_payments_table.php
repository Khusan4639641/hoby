<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDetailPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detail_payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_id')->unique()->comment('our generated id for transaction service');
            $table->string('ext_id')->unique()->nullable()->comment('response id from transaction service');
            $table->bigInteger('amount')->comment('here is amount into tiin (* 100)');
            $table->char('sender_account','20');
            $table->char('sender_mfo','5');
            $table->string('sender_name');
            $table->char('receiver_account','20');
            $table->char('receiver_mfo','5');
            $table->string('receiver_name');
            $table->string('status')->comment('00 -> created, 01 -> success , 02 -> error, 03 -> canceled');
            $table->string('payment_detail')->nullable();
            $table->string('payment_memorial')->nullable();
            $table->integer('payment_type')->nullable();
            $table->integer('payment_state')->nullable();
            $table->timestamp('payment_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detail_payments');
    }
}
