<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use \App\Models\Menu;

class InsertCollectorsLinksIntoMenuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Menu::updateOrCreate(
            ['name' => 'collectors'],
            [
                'route' => 'panel.recovery.collectors.collectors', 
                'permission' => 'show-admin',
                'position' => 'left',
                'type' => 'panel',
                'sort' => 3,
                'parent_id' => 210
            ]
        );
        Menu::updateOrCreate(
            ['name' => 'collector_contracts'],
            [
                'route' => 'panel.recovery.collectors.contracts', 
                'permission' => 'show-admin',
                'position' => 'left',
                'type' => 'panel',
                'sort' => 4,
                'parent_id' => 210
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
        Menu::where('name', 'collectors')->delete();
        Menu::where('name', 'collector-contracts')->delete();
    }
}
