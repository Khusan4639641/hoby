<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsMfoAndNkoColumnsToGeneralCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasColumns('general_companies', ['is_mfo', 'nko'])) {
            Schema::table('general_companies', function (Blueprint $table) {
                $table->unsignedTinyInteger('is_mfo')->default(0)->comment('Принадлежность к МФО');
                $table->string('nko')->nullable()->comment('Код небанковсковской кредитной организации');
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
        if(Schema::hasColumns('general_companies', ['is_mfo', 'nko'])) {
            Schema::table('general_companies', function (Blueprint $table) {
                $table->dropColumn('is_mfo');
                $table->dropColumn('nko');
            });
        }
    }
}
