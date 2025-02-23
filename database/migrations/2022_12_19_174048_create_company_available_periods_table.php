<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyAvailablePeriodsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_available_periods', function (Blueprint $table) {
            $table->id();
            $table->integer('company_id')->unsigned()->index();
            $table->integer('period_id')->unsigned()->index();
            $table->timestamps();

            $table->unique(['period_id','company_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_available_periods');
    }
}
