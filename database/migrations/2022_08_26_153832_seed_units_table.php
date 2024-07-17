<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use \App\Models\Unit;

class SeedUnitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Unit::updateOrCreate(
            ['title' => 'шт'],
            ['title' => 'шт',]
        );

        Unit::updateOrCreate(
            ['title' => 'кг'],
            ['title' => 'кг',]
        );

        Unit::updateOrCreate(
            ['title' => 'тонна'],
            ['title' => 'тонна',]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
