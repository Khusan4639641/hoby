<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUzTaxErrorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('uz_tax_errors', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("payment_id");
            $table->bigInteger("contract_id");
            $table->bigInteger("receipt_id");
            $table->smallInteger('error_code');
            $table->text("json_data");
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
        Schema::dropIfExists('uz_tax_errors');
    }
}
