<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserCreatorsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_creators', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('creator_id')->nullable();
            $table->string('ip_address',50)->nullable();
            $table->timestamps();
        });

        Schema::table('user_creators', function (Blueprint $table){
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_creators');
    }
}
