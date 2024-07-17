<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Create1cAccountBalanceHistoriesTable extends Migration
{
    public function up(): void
    {
        Schema::create('account_balance_histories_1c', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mfo_account_id')->index();
            $table->dateTime('operation_date')->useCurrent();
            $table->decimal('balance',16,2);
            $table->timestamps();

            $table->foreign('mfo_account_id')->references('id')->on('mfo_accounts');
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('account_balance_histories_1c');
    }
}
