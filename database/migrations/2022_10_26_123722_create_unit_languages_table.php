<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use \App\Models\UnitTranslation;

class CreateUnitLanguagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unit_languages', function (Blueprint $table) {
            $table->id();
            $table->string('language_code', 5)->nullable();
            $table->bigInteger('unit_id')->index()->unsigned()->nullable();
            $table->string('title', 255)->nullable();
            $table->timestamps();

            $table->foreign('unit_id')->references('id')->on('units');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unit_languages');
    }
}
