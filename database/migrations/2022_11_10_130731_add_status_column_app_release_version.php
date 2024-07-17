<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusColumnAppReleaseVersion extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mobile_app_releases', function (Blueprint $table) {
            $table->integer('status')->default(1)->comment('in-app update -> 3, flex update -> 2, no need update -> 1');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mobile_app_releases', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}
