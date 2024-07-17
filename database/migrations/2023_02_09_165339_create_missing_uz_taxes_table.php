<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMissingUzTaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('missing_uz_taxes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('contract_id');
            $table->foreign('contract_id')->references('id')->on('contracts');
            $table->unsignedTinyInteger('quantity')->default(1)->comment('Сколько раз нужно отправить (на случай если было несколько дублирующихся чеков при отправке)');
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
        Schema::dropIfExists('missing_uz_taxes');
    }
}
