<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractNotificationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contract_notifications', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->index();
            $table->integer('contract_id')->unsigned()->index();
            $table->string('title_ru',255);
            $table->string('title_uz',255);
            $table->text('message_ru');
            $table->text('message_uz');
            $table->tinyInteger('status')->default(0)->comment('0 не отправлен, 1 отправлен');
            $table->integer('priority')->comment('1 истечение срока, 2 ожидаемый, 3 закрыто');
            $table->string('type',50)->comment('featured,expired,closed');
            $table->timestamps();
        });

        Schema::table('contract_notifications', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('contract_id')->references('id')->on('contracts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contract_notifications');
    }
}
