<?php

use App\Models\MyIDJob;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTypeFieldToMyidJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('myid_jobs', function (Blueprint $table) {
            $table->string('type',50)->default(MyIDJob::TYPE_REGISTRATION)->after('photo_from_camera');
            $table->string('pinfl',14)->after('pass_data')->nullable();
            $table->string('pass_data',10)->nullable()->change();
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
            $table->dropColumn(['type','pinfl']);
        });
    }
}
