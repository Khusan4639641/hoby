<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCollectCostTableAddedExpDaysField extends Migration
{
    public function up()
    {
        Schema::table('collect_cost', function (Blueprint $table) {
            $table->unsignedSmallInteger('exp_days')->nullable();
        });
    }

    public function down()
    {
        Schema::table('collect_cost', function (Blueprint $table) {
            $table->dropColumn('exp_days');
        });
    }
}
