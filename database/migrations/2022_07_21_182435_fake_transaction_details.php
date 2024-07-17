<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FakeTransactionDetails extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fake_transaction_details', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('type',50);
            $table->integer('general_company_id');
            $table->integer('fake_transaction_id');
            $table->date('date');
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
        Schema::drop('fake_transaction_details');
    }
}
