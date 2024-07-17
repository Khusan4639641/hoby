<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\MfoSettings;

class AddColumnToMfoSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mfo_settings', function (Blueprint $table) {
            $table->string('payment_purpose_code')->comment("Мемориальный ордер - спр 26");
        });
        $mfoSettings = MfoSettings::query()->first();
        $mfoSettings->payment_purpose_code = '06';
        $mfoSettings->save();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mfo_settings', function (Blueprint $table) {
            $table->dropColumn('payment_purpose_code');
        });
    }
}
