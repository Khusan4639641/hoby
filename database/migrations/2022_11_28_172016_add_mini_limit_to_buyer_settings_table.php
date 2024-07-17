<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMiniLimitToBuyerSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('buyer_settings', function (Blueprint $table) {
            $table->decimal('mini_limit', 16, 2)->default(0);
            $table->decimal('mini_balance', 16, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('buyer_settings', function (Blueprint $table) {
            $table->dropColumn('mini_limit');
            $table->dropColumn('mini_balance');
        });
    }
}
