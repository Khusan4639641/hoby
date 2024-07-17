<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Create1cAccountsTempTable extends Migration
{
    public function up(): void
    {
        Schema::create('accounts_1c_temp', function (Blueprint $table) {
            $table->collation = 'utf8mb4_unicode_ci';
            $table->id();
            $table->integer('contract_id');
            $table->string('number', 20);
            $table->string('mark', 20);
            $table->bigInteger('debit_account_sum');
            $table->bigInteger('credit_account_sum');
            $table->bigInteger('balance_history_from');
            $table->bigInteger('balance_history_to');
            $table->tinyInteger('contract_bind')->nullable()->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts_1c_temp');
    }
}
