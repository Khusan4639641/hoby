<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\CardType;

class InsertCardTypesToCardTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        CardType::updateOrCreate(
            [
                'name' => 'UZCARD',
                'prefix' => '8600',
                'type_id' => 1,
                'description' => '',
            ]
        );
        CardType::updateOrCreate(
            [
                'name' => 'UZCARD',
                'prefix' => '5614',
                'type_id' => 1,
                'description' => '',
            ]
        );
        CardType::updateOrCreate(
            [
                'name' => 'HUMO',
                'prefix' => '9860',
                'type_id' => 2,
                'description' => '',
            ]
        );
        CardType::updateOrCreate(
            [
                'name' => 'HUMO',
                'prefix' => '4073',
                'type_id' => 2,
                'description' => '',
            ]
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        CardType::whereIn('prefix', ['8600', '5614', '9860', '4073'])->delete();
    }
}
