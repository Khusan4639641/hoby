<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use \App\Models\Menu;

class CreateContractVerifyMenuItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Menu::updateOrCreate(
            ['name' => 'contracts_pending_approval'],
            [
                'route' => 'panel.contract-verify.index',
                'permission' => 'modify-contract',
                'position' => 'left',
                'type' => 'panel',
                'sort' => 6,
                'parent_id' => 106
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
        Menu::where('name', 'contracts_pending_approval')->delete();
    }
}
