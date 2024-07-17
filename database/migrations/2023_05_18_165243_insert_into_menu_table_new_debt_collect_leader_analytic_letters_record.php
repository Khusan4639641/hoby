<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertIntoMenuTableNewDebtCollectLeaderAnalyticLettersRecord extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Menu::create([
            "route"             => "panel.debtCollectLeader.analytic.letters",
            "permission"        => "debt-collect-leader",
            "position"          => "left",
            "type"              => "panel",
            "name"              => "debtCollectLeaderAnalyticLetters",
            "sort"              => "3",
            "parent_id"         => "236"
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Menu::wherePermission("debt-collect-leader")
            ->whereName("debtCollectLeaderAnalyticLetters")
            ->whereRoute("panel.debtCollectLeader.analytic.letters")
            ->delete()
        ;
    }
}
