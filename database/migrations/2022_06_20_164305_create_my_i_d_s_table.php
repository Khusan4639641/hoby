<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMyIDSTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('my_id', function (Blueprint $table) {
            $table->id();
            $table->text('access_token');
            $table->dateTime('expires_in');
            $table->string('token_type',20)->default('Bearer');
            $table->string('scope',250)->nullable();
            $table->text('refresh_token');
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
        Schema::dropIfExists('my_id');
    }
}
