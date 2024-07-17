<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class AlterPartnerSettingsTableAddIsMiniScoringEnabledColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('partner_settings', 'is_mini_scoring_enabled')) {
            Schema::table('partner_settings', function (Blueprint $table) {
                $table
                    ->boolean('is_mini_scoring_enabled')
                    ->nullable()
                    ->after('is_trustworthy')
                    ->default(1)
                    ->comment('Мини-скоринг: Включить - 1/Отключить - 0')
                ;
            });
        }
        if (!Schema::hasColumn('partner_settings', 'is_scoring_enabled')) {
            Schema::table('partner_settings', function (Blueprint $table) {
                $table
                    ->boolean('is_scoring_enabled')
                    ->nullable()
                    ->after('is_trustworthy')
                    ->default(1)
                    ->comment('Основной скоринг: Включить - 1/Отключить - 0')
                ;
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if(Schema::hasColumn('partner_settings', 'is_mini_scoring_enabled')) {
            Schema::table('partner_settings', function (Blueprint $table) {
                $table->dropColumn('is_mini_scoring_enabled');
            });
        }
        if(Schema::hasColumn('partner_settings', 'is_scoring_enabled')) {
            Schema::table('partner_settings', function (Blueprint $table) {
                $table->dropColumn('is_scoring_enabled');
            });
        }
    }
}
