<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class KatmV3Response extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('katm_v3_responses', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->index();
            $table->string('claim_id',255);
            $table->json('params')->nullable();
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
        Schema::dropIfExists('katm_v3_responses');
    }
}
