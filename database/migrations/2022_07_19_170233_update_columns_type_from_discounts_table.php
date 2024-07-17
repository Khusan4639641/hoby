<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateColumnsTypeFromDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->decimal('discount_3', 3, 2)->change();
            $table->decimal('discount_6', 3, 2)->change();
            $table->decimal('discount_9', 3, 2)->change();
            $table->decimal('discount_12', 3, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->float('discount_3', 3, 2)->change();
            $table->float('discount_6', 3, 2)->change();
            $table->float('discount_9', 3, 2)->change();
            $table->float('discount_12', 3, 2)->change();
        });
    }
}
