<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKatmReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('katm_reports', function (Blueprint $table) {
            $table->id();

            $table->string('report_number', 5);
            $table->string('report_type');
            $table->unsignedInteger('contract_id');
            $table->tinyInteger('status');
            $table->integer('order')->default(0);
            $table->text('body');
            $table->text('error_response')->nullable();
            $table->dateTime('sent_date')->nullable();
            $table->timestamps();
        });

        Schema::table('katm_reports', function (Blueprint $table) {
            $table->foreign('contract_id')
                ->references('id')
                ->on('contracts');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('katm_reports', function (Blueprint $table) {
            $table->dropForeign('katm_reports_contract_id_foreign');
        });
        Schema::dropIfExists('katm_reports');
    }
}
