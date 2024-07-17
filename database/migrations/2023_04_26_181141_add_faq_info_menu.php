<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFaqInfoMenu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $parent_editor = Menu::firstOrCreate(
            [
                'route' => 'panel.faqinfo.index',
                'permission' => 'editor',
                'position' => 'left',
                'type' => 'panel',
                'name' => 'faq_info',
                'sort' => 130,
                'parent_id' => null,
                'hash' => '',
                'user_status' => null,
                'denied_affiliate' => null,
                'attr' => null,
                'class' => null,
            ]);

        Menu::firstOrCreate(
            [
                'route' => 'panel.faqinfo.index',
                'permission' => 'editor',
                'position' => 'left',
                'type' => 'panel',
                'name' => 'faq_info_sub',
                'sort' => 1,
                'parent_id' => $parent_editor->id,
                'hash' => '',
                'user_status' => null,
                'denied_affiliate' => null,
                'attr' => null,
                'class' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if($menu = Menu::where('route','panel.faqinfo.index')){
            $menu->delete();
        }
    }
}
