<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountBalanceHistoryCBUSTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('account_balance_histories_cbu', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->index();
            $table->dateTime('operation_date')->useCurrent();
            $table->decimal('balance',16,2);
            $table->timestamps();
        });

        Schema::table('account_balance_histories_cbu', function (Blueprint $table) {
            $table->foreign('account_id')->references('id')->on('accounts_cbu');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_balance_histories_cbu');
    }
}
