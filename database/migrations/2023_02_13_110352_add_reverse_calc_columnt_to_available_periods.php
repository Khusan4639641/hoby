<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReverseCalcColumntToAvailablePeriods extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('available_periods', function (Blueprint $table) {
            $table->tinyInteger('reverse_calc')->default(0);
        });

        \App\Models\AvailablePeriod::query()->where('period','=','003')->update(['reverse_calc' => 1]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('available_periods', function (Blueprint $table) {
            $table->dropColumn(['reverse_calc']);
        });
    }
}
