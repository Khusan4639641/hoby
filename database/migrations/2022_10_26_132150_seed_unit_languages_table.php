<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use \App\Models\Unit;
use \App\Models\UnitLanguage;

class SeedUnitLanguagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $unit_piece = Unit::where('title', 'шт')->first();

        if ($unit_piece) {
            UnitLanguage::updateOrCreate(
                [
                    'unit_id' => $unit_piece->id,
                    'language_code' => 'ru',
                    'title' => 'шт'
                ],
                [
                    'unit_id' => $unit_piece->id,
                    'language_code' => 'ru',
                    'title' => 'шт'
                ]
            );
            UnitLanguage::updateOrCreate(
                [
                    'unit_id' => $unit_piece->id,
                    'language_code' => 'uz',
                    'title' => 'dona'
                ],
                [
                    'unit_id' => $unit_piece->id,
                    'language_code' => 'uz',
                    'title' => 'dona'
                ]
            );
        }

        $unit_kg = Unit::where('title', 'кг')->first();

        if ($unit_kg) {
            UnitLanguage::updateOrCreate(
                [
                    'unit_id' => $unit_kg->id,
                    'language_code' => 'ru',
                    'title' => 'кг'
                ],
                [
                    'unit_id' => $unit_kg->id,
                    'language_code' => 'ru',
                    'title' => 'кг'
                ]
            );
            UnitLanguage::updateOrCreate(
                [
                    'unit_id' => $unit_kg->id,
                    'language_code' => 'uz',
                    'title' => 'kg'
                ],
                [
                    'unit_id' => $unit_kg->id,
                    'language_code' => 'uz',
                    'title' => 'kg'
                ]
            );
        }

        $unit_ton = Unit::where('title', 'тонна')->first();

        if ($unit_ton) {
            UnitLanguage::updateOrCreate(
                [
                    'unit_id' => $unit_ton->id,
                    'language_code' => 'ru',
                    'title' => 'тонна'
                ],
                [
                    'unit_id' => $unit_ton->id,
                    'language_code' => 'ru',
                    'title' => 'тонна'
                ]
            );
            UnitLanguage::updateOrCreate(
                [
                    'unit_id' => $unit_ton->id,
                    'language_code' => 'uz',
                    'title' => 'tonna'
                ],
                [
                    'unit_id' => $unit_ton->id,
                    'language_code' => 'uz',
                    'title' => 'tonna'
                ]
            );
        }


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
