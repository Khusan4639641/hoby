<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DebtCollectFixAnalyticCollectorsLinkInMenuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('menu', function (Blueprint $table) {
            Menu::where('name', 'debtCollectLeaderAnalyticCurators')->delete();

            $leader_analytic_group = Menu::where('name', 'debtCollectLeaderAnalytic')->first();
            Menu::updateOrCreate(
                ['name' => 'debtCollectLeaderAnalyticDebtors'],
                [
                    'route' => 'panel.debtCollectLeader.analytic.debtors',
                    'permission' => 'debt-collect-leader',
                    'position' => 'left',
                    'type' => 'panel',
                    'sort' => 1,
                    'parent_id' => $leader_analytic_group->id,
                ]
            );
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('menu', function (Blueprint $table) {
            //
        });
    }
}
