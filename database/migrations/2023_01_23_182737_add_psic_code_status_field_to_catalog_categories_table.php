<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPsicCodeStatusFieldToCatalogCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasColumn('catalog_categories', 'psic_code_status')) {
            Schema::table('catalog_categories', function (Blueprint $table) {
                $table->unsignedTinyInteger('psic_code_status')->default(0)->comment('0 – не проверена (требуется проверка) 1 – актуальная (можно использовать) 2 – не актуальная (требуется замена, ГНК не предлагает вариант замены)');
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
        if(Schema::hasColumn('catalog_categories', 'psic_code_status')) {
            Schema::table('catalog_categories', function (Blueprint $table) {
                $table->dropColumn('psic_code_status');
            });
        }
    }
}
