<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateColumnsTypeFromPartnerSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('partner_settings', function (Blueprint $table) {
            $table->decimal('discount_1', 16, 2)->change();
            $table->decimal('discount_3', 16, 2)->unsigned()->change();
            $table->decimal('discount_6', 16, 2)->unsigned()->change();
            $table->decimal('discount_9', 16, 2)->unsigned()->change();
            $table->decimal('discount_12', 16, 2)->unsigned()->change();
            $table->decimal('discount_direct', 16, 2)->unsigned()->change();
            $table->decimal('markup_1', 16, 2)->unsigned()->change();
            $table->decimal('markup_3', 16, 2)->unsigned()->change();
            $table->decimal('markup_6', 16, 2)->unsigned()->change();
            $table->decimal('markup_9', 16, 2)->unsigned()->change();
            $table->decimal('markup_12', 16, 2)->unsigned()->change();
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
            $table->float('discount_1', 16, 2)->change();
            $table->float('discount_3', 16, 2)->unsigned()->change();
            $table->float('discount_6', 16, 2)->unsigned()->change();
            $table->float('discount_9', 16, 2)->unsigned()->change();
            $table->float('discount_12', 16, 2)->unsigned()->change();
            $table->float('discount_direct', 16, 2)->unsigned()->change();
            $table->float('markup_1', 16, 2)->unsigned()->change();
            $table->float('markup_3', 16, 2)->unsigned()->change();
            $table->float('markup_6', 16, 2)->unsigned()->change();
            $table->float('markup_9', 16, 2)->unsigned()->change();
            $table->float('markup_12', 16, 2)->unsigned()->change();
        });
    }
}
