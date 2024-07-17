<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateColumnsTypeFromOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('total', 16, 2)->unsigned()->change();
            $table->decimal('partner_total', 16, 2)->unsigned()->change();
            $table->decimal('credit', 16, 2)->unsigned()->change();
            $table->decimal('debit', 16, 2)->unsigned()->change();
            $table->decimal('shipping_price', 16, 2)->unsigned()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->float('total', 16, 2)->unsigned()->change();
            $table->float('partner_total', 16, 2)->unsigned()->change();
            $table->float('credit', 16, 2)->unsigned()->change();
            $table->float('debit', 16, 2)->unsigned()->change();
            $table->float('shipping_price', 16, 2)->unsigned()->change();
        });
    }
}
