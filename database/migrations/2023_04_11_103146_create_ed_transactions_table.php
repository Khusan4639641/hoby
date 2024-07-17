<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEdTransactionsTable extends Migration
{
    public function up(): void
    {
        Schema::create('ed_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('doc_id', 32)->comment("идентификатор документа в банковской системе");
            $table->unsignedBigInteger('doc_time')->comment("дата документа (UNIX-метка времени в миллисекундах)");
            $table->string('doc_type')->nullable()->comment('тип документа');
            $table->bigInteger('amount')->comment("сумма в тийинах");
            $table->string('purpose_of_payment')->comment("назначение платежа");
            $table->string('cash_symbol', 8)->comment('Коды валют по ISO 4217');
            $table->string('corr_name', 256)->comment("Имя корреспондента");
            $table->string('corr_account', 64)->comment("Счет корреспондента");
            $table->string('corr_mfo', 16)->comment("Мфо корреспондента");
            $table->string('corr_inn',16)->nullable()->comment("Инн корреспондента");
            $table->string('corr_bank', 255)->nullable()->comment("Банк корреспондента");
            $table->string('type')->comment('DEBIT или CREDIT');
            $table->timestamps();

            $table->index(['doc_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ed_transactions');
    }
}
