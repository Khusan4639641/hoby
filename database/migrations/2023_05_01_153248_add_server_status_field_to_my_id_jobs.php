<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddServerStatusFieldToMyIdJobs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('myid_jobs', function (Blueprint $table) {
            $table->string('status_code',3)->default(200)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('myid_jobs', function (Blueprint $table) {
            $table->dropColumn('status_code');
        });
    }
}
