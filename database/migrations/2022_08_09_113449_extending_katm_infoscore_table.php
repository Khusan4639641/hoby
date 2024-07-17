<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ExtendingKatmInfoscoreTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('katm_infoscore', function (Blueprint $table) {
            $table->string('claim_id', '20')->nullable()->change();
            $table->longText('response')->nullable()->change();
            $table->string('token', '32')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('katm_infoscore', function (Blueprint $table) {
            $table->dropColumn('token');
        });
    }
}
