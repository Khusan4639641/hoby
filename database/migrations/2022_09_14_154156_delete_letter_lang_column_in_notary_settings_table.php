<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteLetterLangColumnInNotarySettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(Schema::hasColumn('notary_settings', 'letter_lang')) {
            Schema::table('notary_settings', function (Blueprint $table) {
                $table->dropColumn('letter_lang');
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
        Schema::table('notary_settings', function (Blueprint $table) {

        });
    }
}
