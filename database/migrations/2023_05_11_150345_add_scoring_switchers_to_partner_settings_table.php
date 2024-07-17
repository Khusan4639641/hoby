<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddScoringSwitchersToPartnerSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('partner_settings', function (Blueprint $table) {
            $table->boolean('is_scoring_enabled')->index()->default(1);
            $table->boolean('is_mini_scoring_enabled')->index()->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('partner_settings', function (Blueprint $table) {
            $table->dropColumn(['is_scoring_enabled', 'is_mini_scoring_enabled']);
        });
    }
}
