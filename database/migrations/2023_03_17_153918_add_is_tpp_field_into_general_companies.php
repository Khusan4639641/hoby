<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsTppFieldIntoGeneralCompanies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table("general_companies", static function (Blueprint $table) {
            $table->boolean("is_tpp")->default(0)->after("is_mfo");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table("general_companies", static function (Blueprint $table) {
            $table->dropColumn("is_tpp");
        });
    }
}
