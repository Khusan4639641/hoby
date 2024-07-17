<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUzTaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('uz_taxes', function (Blueprint $table) {
            $table->id();
            $table->bigInteger("payment_id")->comment('The Payment id')->default(0);
            $table->bigInteger("contract_id")->comment('The Contract id or ReceiptId')->default(0);
            $table->bigInteger("fiscal_sign")->comment('contains fiscal sign number ')->default(0);
            $table->tinyInteger("status")->comment('cancel = 0 or accept = 1 and wait = 2')->default(2);
            $table->tinyInteger("type")->comment('sell = 0; prepaid = 1; credit = 2')->nullable();
            $table->string("terminal_id")->comment('The TerminalID field contains the number of the Virtual Fiscal Module')->default(0);
            $table->string("payment_system")->nullable();
            $table->string("qr_code_url")->comment('The URL displays a QR code')->nullable();
            $table->text("json_data")->comment('Payment information is converted to JSON')->nullable();
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
        Schema::dropIfExists('uz_taxes');
    }
}
