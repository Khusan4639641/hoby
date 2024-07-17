<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableMfoSettings extends Migration
{
    public function up(): void
    {
        Schema::table('mfo_settings', function (Blueprint $table) {
            $table->integer('general_company_id');
        });
    }

    public function down(): void
    {
        Schema::table('mfo_settings', function (Blueprint $table) {
            $table->dropColumn('general_company_id');
        });
    }
}
