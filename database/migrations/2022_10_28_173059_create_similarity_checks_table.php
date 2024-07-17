<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSimilarityChecksTable extends Migration
{
    public function up()
    {
        Schema::create('similarity_checks', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->string('user_name')->nullable();
            $table->string('user_surname')->nullable();
            $table->string('user_patronymic')->nullable();
            $table->integer('card_id')->unsigned();
            $table->foreign('card_id')->references('id')->on('cards');
            $table->string('card_name')->nullable();
            $table->text('card_number')->nullable();
            $table->text('card_valid_date')->nullable();
            $table->string('similarity_percent_fw');
            $table->string('similarity_percent_rev');
            $table->string('min_percent');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('similarity_checks');
    }
}
