<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateColumnsTypeFromBuyerSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('buyer_settings', function (Blueprint $table) {
            $table->decimal('balance', 16, 2)->change();
            $table->decimal('zcoin', 16, 2)->change();
            $table->decimal('paycoin', 16, 4)->unsigned()->change();
            $table->decimal('personal_account', 16, 2)->unsigned()->change();
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
            $table->float('balance', 16, 2)->change();
            $table->float('zcoin', 16, 2)->change();
            $table->float('paycoin', 16, 2)->unsigned()->change();
            $table->float('personal_account', 16, 2)->unsigned()->change();
        });
    }
}
