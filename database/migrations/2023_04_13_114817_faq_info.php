<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FaqInfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('faq_info', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->integer('sort');
            $table->string('question_ru',255);
            $table->text('answer_ru');
            $table->string('question_uz',255);
            $table->text('answer_uz');
            $table->tinyInteger('status')->default(1)->comment("статус вопроса (0 – неактивный вопрос; 1 – активный вопрос)");
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
        Schema::dropIfExists('faq_info');
    }
}
