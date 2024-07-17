<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtCollectDebtorActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('debt_collect_debtor_actions', function (Blueprint $table) {
            $table->id();
            $table->integer('debtor_id');
            $table->integer('collect_agent_id');
            $table->string('type');
            $table->text('content')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('debtor_id')->references('id')->on('users');
            $table->foreign('collect_agent_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('debt_collect_debtor_actions');
    }
}
