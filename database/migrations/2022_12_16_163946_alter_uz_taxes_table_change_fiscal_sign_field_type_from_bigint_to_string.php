<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterUzTaxesTableChangeFiscalSignFieldTypeFromBigintToString extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('uz_taxes', function (Blueprint $table) {
            $table->string('fiscal_sign', 12)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('uz_taxes', function (Blueprint $table) {
            $table->bigInteger('fiscal_sign')->change();
        });
    }
}
