<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeUniqNumFieldOnCompanies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('uniq_num',100)->change();
        });

        Schema::table('company_uniq_nums', function (Blueprint $table) {
            $table->string('uniq_num',100)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->integer('uniq_num')->change();
        });

        Schema::table('company_uniq_nums', function (Blueprint $table) {
            $table->integer('uniq_num')->change();
        });
    }
}
