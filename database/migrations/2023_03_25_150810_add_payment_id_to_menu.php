<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPaymentIdToMenu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $parentCategory = Menu::create([
            'route' => '',
            'permission' => 'show-admin',
            'position' => 'left',
            'type' => 'panel',
            'name' => 'payment_id_category',
            'sort' => 1,
            'parent_id' => NULL,
        ]);

        Menu::create([
            'route' => 'panel.payment-info.payment-by-id',
            'permission' => 'show-admin',
            'position' => 'left',
            'type' => 'panel',
            'name' => 'payment_id_payments',
            'sort' => 1,
            'parent_id' => $parentCategory->id,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Menu::where('name','payment_id_category')->delete();
        Menu::where('name','payment_id_payments')->delete();

    }
}
