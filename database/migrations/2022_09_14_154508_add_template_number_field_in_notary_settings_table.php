<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTemplateNumberFieldInNotarySettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notary_settings', function (Blueprint $table) {
            $table->string('template_number')->comment('Номер шаблона исполнительного письма');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notary_settings', function (Blueprint $table) {
            $table->dropColumn('template_number');
        });
    }
}
