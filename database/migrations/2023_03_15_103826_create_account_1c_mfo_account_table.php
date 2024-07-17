<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccount1cMfoAccountTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_1c_mfo_account', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_1c_id')->references('id')->on('accounts_1c');
            $table->foreignId('mfo_account_id')->references('id')->on('mfo_accounts');
            $table->unique(['account_1c_id', 'mfo_account_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_1c_mfo_account');
    }
}
