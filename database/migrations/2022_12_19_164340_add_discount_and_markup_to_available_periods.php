<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDiscountAndMarkupToAvailablePeriods extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('available_periods', function (Blueprint $table) {
            $table->decimal('discount')->default(0);
            $table->decimal('markup')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('available_periods', function (Blueprint $table) {
            $table->dropColumn('discount');
            $table->dropColumn('markup');
        });
    }
}
