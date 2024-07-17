<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMyIDJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('myid_jobs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->index();
            $table->string('job_id',250)->index();
            $table->integer('photo_from_camera')->unsigned()->index()->nullable();
            $table->string('pass_data',10);
            $table->date('birth_date');
            $table->boolean('agreed_on_terms')->default(true);
            $table->string('external_id',250);
            $table->float('comparison_value',200)->nullable();
            $table->string('result_code',10)->nullable();
            $table->string('result_note',250)->nullable();
            $table->longText('profile')->nullable();
            $table->timestamps();
        });

        Schema::table('myid_jobs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('photo_from_camera')->references('id')->on('files');
        });
        
       
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('myid_jobs');
    }
}
