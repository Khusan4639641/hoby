<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsDefiniteFieldToCatalogCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasColumn('catalog_categories', 'is_definite')) {
            Schema::table('catalog_categories', function (Blueprint $table) {
                $table->boolean('is_definite')->default(true)->comment("Определяет, точная ли категория или 'прочие'");
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if(Schema::hasColumn('catalog_categories', 'is_definite')) {
            Schema::table('catalog_categories', function (Blueprint $table) {
                $table->dropColumn('is_definite');
            });
        }
    }
}
