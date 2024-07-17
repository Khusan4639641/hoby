<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterNotarySettingsTableAddedTaxField extends Migration
{
    public function up()
    {
        Schema::table('notary_settings', function (Blueprint $table) {
            $table->enum('tax', [1, 0])->default(1);
        });
    }

    public function down()
    {
        Schema::table('notary_settings', function (Blueprint $table) {
            $table->dropColumn('tax');
        });
    }
}
