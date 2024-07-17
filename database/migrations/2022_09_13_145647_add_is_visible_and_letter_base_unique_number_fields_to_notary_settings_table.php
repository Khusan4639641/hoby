<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsVisibleAndLetterBaseUniqueNumberFieldsToNotarySettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notary_settings', function (Blueprint $table) {
            $table->string('letter_base_unique_number')->nullable();
            $table->boolean('is_visible')->default(1);
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
            $table->dropColumn('letter_base_unique_number');
            $table->dropColumn('is_visible');
        });
    }
}
