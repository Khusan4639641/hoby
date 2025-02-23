<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImagePathChangingPhoneNumber extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kyc_history', function (Blueprint $table) {
            $table->text('image')->nullable();
            $table->text('old_phone')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kyc_history', function (Blueprint $table) {
            $table->dropColumn('image');
            $table->dropColumn('old_phone');
        });
    }
}
