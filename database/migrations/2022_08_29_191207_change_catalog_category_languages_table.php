<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeCatalogCategoryLanguagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('catalog_category_languages', function (Blueprint $table) {
            $table->text('preview_text')->nullable()->change();
            $table->text('detail_text')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('catalog_category_languages', function (Blueprint $table) {
            $table->text('preview_text')->change();
            $table->text('detail_text')->change();
        });
    }
}
