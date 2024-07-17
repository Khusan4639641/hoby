<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKatmReportsTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('katm_received_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_type', 20);
            $table->unsignedInteger('contract_id');
            $table->foreign('contract_id')->references('id')->on('contracts');
            $table->string("token", 64);
            $table->unsignedInteger("file_id")->nullable();
            $table->foreign('file_id')->references('id')->on('files');
            $table->text('error_response')->nullable();
            $table->dateTime('received_date')->nullable();
            $table->tinyInteger('status');
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
        Schema::dropIfExists('katm_received_reports');
    }
}
