<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToCatalogCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('catalog_categories', function (Blueprint $table) {
            $table->string('psic_code', 255)->nullable();
            $table->text('psic_text')->nullable();
            $table->integer('marketplace_id')->unsigned()->nullable();
            $table->tinyInteger('status')->unsigned()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('catalog_categories', function (Blueprint $table) {
            $table->dropColumn('psic_code');
            $table->dropColumn('psic_text');
            $table->dropColumn('marketplace_id');
            $table->dropColumn('status');
        });
    }
}
