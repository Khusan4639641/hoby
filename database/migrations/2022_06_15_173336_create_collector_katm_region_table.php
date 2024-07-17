<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCollectorKatmRegionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('collector_katm_region', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collector_id')
                ->constrained();
            $table->unsignedInteger('katm_region_id')->unique();
            $table->timestamps();
            
            $table->foreign('katm_region_id')
                ->references('id')
                ->on('katm_regions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('collector_katm_region');
    }
}
