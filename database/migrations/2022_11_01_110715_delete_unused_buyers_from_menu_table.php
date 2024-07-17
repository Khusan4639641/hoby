<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;

class DeleteUnusedBuyersFromMenuTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Menu::    // #verified
//            destroy(92);
              where("route", "panel.buyers.index")
            ->where("permission", "modify-buyer")
            ->where("position", "left")
            ->where("type", "panel")
            ->where("name", "verified")
            ->where("sort", 2)
            ->where("parent_id", 88)
            ->where("hash", "#verified")
            ->delete()
        ;

        Menu::    // #verification
//            destroy(94);
              where("route", "panel.buyers.index")
            ->where("permission", "modify-buyer")
            ->where("position", "left")
            ->where("type", "panel")
            ->where("name", "verification")
            ->where("sort", 3)
            ->where("parent_id", 88)
            ->where("hash", "#verification")
            ->delete()
        ;

        Menu::    // #correction
//            destroy(96);
              where("route", "panel.buyers.index")
            ->where("permission", "modify-buyer")
            ->where("position", "left")
            ->where("type", "panel")
            ->where("name", "correction")
            ->where("sort", 4)
            ->where("parent_id", 88)
            ->where("hash", "#correction")
            ->delete()
        ;
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Menu::insert([
            [    // #verified
                "id"         => 92,
                "route"      => "panel.buyers.index",
                "permission" => "modify-buyer",
                "position"   => "left",
                "type"       => "panel",
                "name"       => "verified",
                "sort"       => 2,
                "parent_id"  => 88,
                "hash"       => "#verified",
            ],
            [    // #verification
                "id"         => 94,
                "route"      => "panel.buyers.index",
                "permission" => "modify-buyer",
                "position"   => "left",
                "type"       => "panel",
                "name"       => "verification",
                "sort"       => 3,
                "parent_id"  => 88,
                "hash"       => "#verification",
            ],
            [    // #correction
                "id"         => 96,
                "route"      => "panel.buyers.index",
                "permission" => "modify-buyer",
                "position"   => "left",
                "type"       => "panel",
                "name"       => "correction",
                "sort"       => 4,
                "parent_id"  => 88,
                "hash"       => "#correction",
            ]
        ]);
    }
}
