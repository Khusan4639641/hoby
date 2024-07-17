<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class BlockingHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('blocking_history')) {
            Schema::create('blocking_history', function (Blueprint $table) {
                $table->id();
                $table->integer('company_id');
                $table->integer('type');
                $table->integer('user_id')->nullable();
                $table->integer('manager_id')->nullable();
                $table->integer('reason_id')->nullable();
                $table->text('comment')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('blocking_history')) {
            Schema::dropIfExists('blocking_history');
        }
    }
}
