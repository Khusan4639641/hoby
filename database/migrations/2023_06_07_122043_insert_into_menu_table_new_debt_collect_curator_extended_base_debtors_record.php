<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use App\Models\Menu;

class InsertIntoMenuTableNewDebtCollectCuratorExtendedBaseDebtorsRecord extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $curator_extended_base = Menu::create([
            "route"      => "",
            "permission" => "debt-collect-curator-extended",
            "position"   => "left",
            "type"       => "panel",
            "name"       => "debtCollectCuratorExtendedBase",
            "sort"       => 1,
            "parent_id"  => NULL,
        ]);

        Menu::create([
            "route"      => "panel.debtCollectCuratorExtended.collectors",
            "permission" => "debt-collect-curator-extended",
            "position"   => "left",
            "type"       => "panel",
            "name"       => "debtCollectCuratorExtendedCollectors",
            "sort"       => 1,
            "parent_id"  => $curator_extended_base->id,
        ]);

        Menu::create([
            "route"      => "panel.debtCollectCuratorExtended.debtors",
            "permission" => "debt-collect-curator-extended",
            "position"   => "left",
            "type"       => "panel",
            "name"       => "debtCollectCuratorExtendedDebtors",
            "sort"       => 2,
            "parent_id"  => $curator_extended_base->id
        ]);

        $curator_extended_analytic = Menu::create([
            "route"      => "",
            "permission" => "debt-collect-curator-extended",
            "position"   => "left",
            "type"       => "panel",
            "name"       => "debtCollectCuratorExtendedAnalytic",
            "sort"       => 1,
            "parent_id"  => NULL,
        ]);

        Menu::create([
            "route"      => "panel.debtCollectCuratorExtended.analytic.collectors",
            "permission" => "debt-collect-curator-extended",
            "position"   => "left",
            "type"       => "panel",
            "name"       => "debtCollectCuratorExtendedAnalyticCollectors",
            "sort"       => 1,
            "parent_id"  => $curator_extended_analytic->id,
        ]);
        Menu::create([
            "route"      => "panel.debtCollectCuratorExtended.analytic.debtors",
            "permission" => "debt-collect-curator-extended",
            "position"   => "left",
            "type"       => "panel",
            "name"       => "debtCollectCuratorExtendedAnalyticDebtors",
            "sort"       => 2,
            "parent_id"  => $curator_extended_analytic->id,
        ]);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $curator_extended_base = Menu::wherePermission("debt-collect-curator-extended")
            ->whereName("debtCollectCuratorExtendedBase")
            ->whereSort(1)
            ->whereNull("parent_id")
            ->first()
        ;
        Menu::wherePermission("debt-collect-curator-extended")
            ->whereName("debtCollectCuratorExtendedCollectors")
            ->whereSort(1)
            ->whereParentId($curator_extended_base->id)
            ->whereRoute("panel.debtCollectCuratorExtended.collectors")
            ->delete()
        ;
        Menu::wherePermission("debt-collect-curator-extended")
            ->whereName("debtCollectCuratorExtendedDebtors")
            ->whereSort(2)
            ->whereParentId($curator_extended_base->id)
            ->whereRoute("panel.debtCollectCuratorExtended.debtors")
            ->delete()
        ;
        $curator_extended_base->delete();

        $curator_extended_analytic = Menu::wherePermission("debt-collect-curator-extended")
            ->whereName("debtCollectCuratorExtendedAnalytic")
            ->whereSort(1)
            ->whereNull("parent_id")
            ->first()
        ;
        Menu::wherePermission("debt-collect-curator-extended")
            ->whereName("debtCollectCuratorExtendedAnalyticCollectors")
            ->whereSort(1)
            ->whereParentId($curator_extended_analytic->id)
            ->whereRoute("panel.debtCollectCuratorExtended.analytic.collectors")
            ->delete()
        ;
        Menu::wherePermission("debt-collect-curator-extended")
            ->whereName("debtCollectCuratorExtendedAnalyticDebtors")
            ->whereSort(2)
            ->whereParentId($curator_extended_analytic->id)
            ->whereRoute("panel.debtCollectCuratorExtended.analytic.debtors")
            ->delete()
        ;
        $curator_extended_analytic->delete();
    }
}
