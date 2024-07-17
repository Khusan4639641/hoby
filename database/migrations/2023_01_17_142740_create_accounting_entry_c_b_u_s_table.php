<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use \App\Models\AccountingEntryCBU;

class CreateAccountingEntryCBUSTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounting_entries_cbu', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('status')->default(AccountingEntryCBU::STATUS_ACTIVE);
            $table->dateTime('operation_date')->useCurrent();
            $table->string('debit_account',20)->index();
            $table->string('credit_account',20)->index();
            $table->decimal('amount',16,2);
            $table->string('description',255)->nullable();
            $table->integer('contract_id')->index()->unsigned();
            $table->string('destination_code',4);
            $table->string('payment_id',20)->nullable();
            $table->timestamps();
        });

        Schema::table('accounting_entries_cbu', function (Blueprint $table) {
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
        Schema::dropIfExists('accounting_entries_cbu');
    }
}
