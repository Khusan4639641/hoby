<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeProductNameLengthInLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contract_verify_logs', function (Blueprint $table) {
            $table->string('old_name', 400)->change();
            $table->string('new_name', 400)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contract_verify_logs', function (Blueprint $table) {
            $table->string('old_name', 191)->change();
            $table->string('new_name', 191)->change();
        });
    }
}
