<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AvailablePeriodsClientPhotoUpload extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('available_periods', function (Blueprint $table) {
            $table->boolean('client_photo_upload')->default(false)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('available_periods', function (Blueprint $table) {
            $table->dropColumn('client_photo_upload');
        });
    }
}
