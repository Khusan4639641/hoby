<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccounts1CTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounts_1c', function (Blueprint $table) {
            $table->id();
            $table->string('number', 6);
            $table->string('name');
            $table->boolean('is_subconto');
            $table->string('subconto_number', 20)->nullable();
            $table->timestamps();
            $table->unique(['number', 'subconto_number']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounts_1c');
    }
}
