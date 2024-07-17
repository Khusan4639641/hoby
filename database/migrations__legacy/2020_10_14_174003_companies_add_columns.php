<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CompaniesAddColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->text('short_description')->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('website', 255)->nullable();
            $table->string('email', 255)->nullable();
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
            $table->removeColumn('short_description');
            $table->removeColumn('phone');
            $table->removeColumn('website');
            $table->removeColumn('email');
        });
    }
}
