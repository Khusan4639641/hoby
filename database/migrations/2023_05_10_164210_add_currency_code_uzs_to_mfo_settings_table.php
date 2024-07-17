<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCurrencyCodeUzsToMfoSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mfo_settings', function (Blueprint $table) {
            $table->string('currency_code_uzs', 3)->default('860');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mfo_settings', function (Blueprint $table) {
            $table->dropColumn('currency_code_uzs');
        });
    }
}
