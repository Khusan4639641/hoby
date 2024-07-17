<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobileAppReleasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mobile_app_releases', function (Blueprint $table) {
            $table->id();
            $table->enum('os', ['android', 'ios']);
            $table->string('bundle_name')->comment("Column for getting mobile name it's mat be like (uz.test.test_mobile)");
            $table->string('version')->comment('mobile app release version');
            $table->boolean('is_available')->comment('mobile app is available for release or not');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mobile_app_releases');
    }
}
