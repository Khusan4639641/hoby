<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEventIdToAccountingEntries extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('accounting_entries', function (Blueprint $table) {
            $table->integer('event_id')->nullable();
        });

        Schema::table('accounting_entries_cbu', function (Blueprint $table) {
            $table->integer('event_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounting_entries', function (Blueprint $table) {
            $table->dropColumn(['event_id']);
        });

        Schema::table('accounting_entries_cbu', function (Blueprint $table) {
            $table->dropColumn(['event_id']);
        });
    }
}
