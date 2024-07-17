<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToOrderProductsTable extends Migration
{
    public function up()
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->decimal('original_price', 16)->default(0);
            $table->decimal('total_nds', 16)->default(0);
            $table->decimal('original_price_client', 16)->default(0);
            $table->decimal('total_nds_client', 16)->default(0);
            $table->decimal('used_nds_percent')->default(0);
        });
    }

    public function down()
    {
        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn('used_nds_percent');
            $table->dropColumn('total_nds_client');
            $table->dropColumn('original_price_client');
            $table->dropColumn('total_nds');
            $table->dropColumn('original_price');
        });
    }
}
