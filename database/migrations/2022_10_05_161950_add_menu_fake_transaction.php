<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMenuFakeTransaction extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Menu::firstOrCreate(
            array(
                'route' => 'panel.fake.transaction',
                'permission' => 'show-admin',
                'position' => 'left',
                'type' => 'panel',
                'name' => 'report_fake',
                'sort' => 1,
                'parent_id' => 203,
                'hash' => '',
                'user_status' => null,
                'denied_affiliate' => null,
                'attr' => null,
                'class' => null,
            )
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if($menu = Menu::where('route','panel.fake.transaction')){
            $menu->delete();
        }
    }
}
