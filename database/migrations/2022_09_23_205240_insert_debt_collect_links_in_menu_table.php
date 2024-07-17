<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertDebtCollectLinksInMenuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('menu', function (Blueprint $table) {
            $leader_base = Menu::create([
                'route' => '',
                'permission' => 'debt-collect-leader',
                'position' => 'left',
                'type' => 'panel',
                'name' => 'debtCollectLeaderBase',
                'sort' => 1,
                'parent_id' => NULL,
            ]);

            Menu::create([
                'route' => 'panel.debtCollectLeader.curators',
                'permission' => 'debt-collect-leader',
                'position' => 'left',
                'type' => 'panel',
                'name' => 'debtCollectLeaderCurators',
                'sort' => 1,
                'parent_id' => $leader_base->id,
            ]);
            Menu::create([
                'route' => 'panel.debtCollectLeader.collectors',
                'permission' => 'debt-collect-leader',
                'position' => 'left',
                'type' => 'panel',
                'name' => 'debtCollectLeaderCollectors',
                'sort' => 2,
                'parent_id' => $leader_base->id,
            ]);
            Menu::create([
                'route' => 'panel.debtCollectLeader.debtors',
                'permission' => 'debt-collect-leader',
                'position' => 'left',
                'type' => 'panel',
                'name' => 'debtCollectLeaderDebtors',
                'sort' => 3,
                'parent_id' => $leader_base->id,
            ]);

            $leader_analytic = Menu::create([
                'route' => '',
                'permission' => 'debt-collect-leader',
                'position' => 'left',
                'type' => 'panel',
                'name' => 'debtCollectLeaderAnalytic',
                'sort' => 1,
                'parent_id' => NULL,
            ]);

            Menu::create([
                'route' => 'panel.debtCollectLeader.analytic.curators',
                'permission' => 'debt-collect-leader',
                'position' => 'left',
                'type' => 'panel',
                'name' => 'debtCollectLeaderAnalyticCurators',
                'sort' => 1,
                'parent_id' => $leader_analytic->id,
            ]);
            Menu::create([
                'route' => 'panel.debtCollectLeader.analytic.collectors',
                'permission' => 'debt-collect-leader',
                'position' => 'left',
                'type' => 'panel',
                'name' => 'debtCollectLeaderAnalyticCollectors',
                'sort' => 2,
                'parent_id' => $leader_analytic->id,
            ]);

            $curator_base = Menu::create([
                'route' => '',
                'permission' => 'debt-collect-curator',
                'position' => 'left',
                'type' => 'panel',
                'name' => 'debtCollectCuratorBase',
                'sort' => 1,
                'parent_id' => NULL,
            ]);

            Menu::create([
                'route' => 'panel.debtCollectCurator.collectors',
                'permission' => 'debt-collect-curator',
                'position' => 'left',
                'type' => 'panel',
                'name' => 'debtCollectCuratorCollectors',
                'sort' => 2,
                'parent_id' => $curator_base->id,
            ]);

            $curator_analytic = Menu::create([
                'route' => '',
                'permission' => 'debt-collect-curator',
                'position' => 'left',
                'type' => 'panel',
                'name' => 'debtCollectCuratorAnalytic',
                'sort' => 1,
                'parent_id' => NULL,
            ]);

            Menu::create([
                'route' => 'panel.debtCollectCurator.analytic.collectors',
                'permission' => 'debt-collect-curator',
                'position' => 'left',
                'type' => 'panel',
                'name' => 'debtCollectCuratorAnalyticCollectors',
                'sort' => 1,
                'parent_id' => $curator_analytic->id,
            ]);
            Menu::create([
                'route' => 'panel.debtCollectCurator.analytic.debtors',
                'permission' => 'debt-collect-curator',
                'position' => 'left',
                'type' => 'panel',
                'name' => 'debtCollectCuratorAnalyticDebtors',
                'sort' => 2,
                'parent_id' => $curator_analytic->id,
            ]);
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
