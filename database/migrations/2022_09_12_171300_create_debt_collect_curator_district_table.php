<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDebtCollectCuratorDistrictTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('debt_collect_curator_district', function (Blueprint $table) {
            $table->id();
            $table->integer('curator_id');
            $table->foreignId('district_id')->constrained();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('curator_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('debt_collect_curator_district');
    }
}
