<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FaqInfoHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('faq_info_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('faq_id');
            $table->foreign('faq_id')->references('id')->on('faq_info');
            $table->integer('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->text('previous_ru');
            $table->text('previous_uz');
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
        Schema::dropIfExists('faq_info_histories');
    }
}
